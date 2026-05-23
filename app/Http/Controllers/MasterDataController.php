<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Location;
use App\Models\Department;
use App\Models\ItemCategory;
use App\Models\User;
use App\Http\Resources\ItemResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\ItemCategoryResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MasterDataController extends Controller
{
    public function __construct()
    {
        // Semua method require auth
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['engineer', 'supervisor'])) {
            abort(403, 'Unauthorized.');
        }

        $type = $request->query('type', 'items');
        $state = $request->query('state', 'active');
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);

        $definition = $this->definitionFor($type);

        $query = $definition['model']::query();

            if (in_array($state, ['inactive', 'trashed'], true) && $definition['active_column']) {
            $query->where($definition['active_column'], false);
        } elseif ($state === 'active' && $definition['active_column']) {
            $query->where($definition['active_column'], true);
        }

        if ($definition['with']) {
            $query->with($definition['with']);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($definition, $search) {
                foreach ($definition['search_columns'] as $column) {
                    $builder->orWhere($column, 'LIKE', "%{$search}%");
                }
            });
        }

        $paginator = $query
            ->orderBy($definition['order_by'])
            ->paginate($perPage)
            ->withQueryString();

        $paginator->getCollection()->transform(function ($record) use ($type) {
            return $this->formatRecord($type, $record);
        });

        return Inertia::render($this->viewForType($type), [
            'activeType' => $type,
            'types' => $this->typeCards(),
            'records' => $paginator->toArray(),
            'filters' => [
                'state' => $state,
                'search' => $search,
                'per_page' => $perPage,
            ],
            'columns' => $definition['columns'],
            'fields' => $definition['fields'],
            'typeMeta' => [
                'label' => $definition['label'],
                'description' => $definition['description'],
            ],
            'canManage' => $user->role === 'supervisor',
            'options' => $this->formOptions(),
        ]);
    }

    public function store(Request $request, string $type): JsonResponse|RedirectResponse
    {
        $this->assertSupervisor($request);
        $definition = $this->definitionFor($type);

        $data = $request->validate($this->rulesFor($type));
        $modelClass = $definition['model'];

        $modelClass::create($this->mapPayload($type, $data));

        if ($request->expectsJson()) {
            return response()->json(['message' => $definition['label'] . ' berhasil dibuat.']);
        }

        return redirect()
            ->route('master-data.index', ['type' => $type])
            ->with('success', $definition['label'] . ' berhasil dibuat.');
    }

    public function update(Request $request, string $type, string $record): JsonResponse|RedirectResponse
    {
        $this->assertSupervisor($request);
        $definition = $this->definitionFor($type);
        $modelClass = $definition['model'];

        $instance = $modelClass::findOrFail($record);
        $data = $request->validate($this->rulesFor($type, $instance));

        $instance->fill($this->mapPayload($type, $data));
        $instance->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => $definition['label'] . ' berhasil diperbarui.']);
        }

        return redirect()
            ->route('master-data.index', ['type' => $type])
            ->with('success', $definition['label'] . ' berhasil diperbarui.');
    }

    public function destroy(Request $request, string $type, string $record): JsonResponse|RedirectResponse
    {
        $this->assertSupervisor($request);
        $definition = $this->definitionFor($type);
        $modelClass = $definition['model'];

        $instance = $modelClass::findOrFail($record);
        $instance->fill(['is_active' => false]);
        $instance->save();

            if ($request->expectsJson()) {
                return response()->json(['message' => $definition['label'] . ' berhasil dinonaktifkan.']);
            }

            return redirect()
                ->route('master-data.index', ['type' => $type])
                ->with('success', $definition['label'] . ' berhasil dinonaktifkan.');
    }

    public function restore(Request $request, string $type, string $record): JsonResponse|RedirectResponse
    {
        $this->assertSupervisor($request);
        $definition = $this->definitionFor($type);
        $modelClass = $definition['model'];

        $instance = $modelClass::findOrFail($record);
        $instance->fill(['is_active' => true]);
        $instance->save();

            if ($request->expectsJson()) {
                return response()->json(['message' => $definition['label'] . ' berhasil dipulihkan.']);
            }

            return redirect()
                ->route('master-data.index', ['type' => $type])
                ->with('success', $definition['label'] . ' berhasil dipulihkan.');
    }

    /**
     * GET /master-data/items
     * Untuk dropdown: semua role bisa lihat item (terbatas sesuai kebutuhan)
     */
    public function getItems(Request $request)
    {
        $query = Item::query()
            ->with(['category', 'location', 'department'])
            ->where('is_active', true);

        // Filter by type
        if ($type = $request->query('type')) {
            $query->where('item_type', $type);
        }

        // Filter by category
        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filter by department
        if ($deptId = $request->query('dept_id')) {
            $query->where('dept_id', $deptId);
        }

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Search
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('asset_tag', 'LIKE', "%{$search}%")
                    ->orWhere('item_name', 'LIKE', "%{$search}%")
                    ->orWhere('serial_number', 'LIKE', "%{$search}%");
            });
        }

        // Untuk dropdown biasanya ambil semua, tapi bisa di-limit
        $limit = $request->query('limit', 100);
        $items = $query->orderBy('item_name')->limit($limit)->get();

        return response()->json([
            'data' => ItemResource::collection($items),
            'total' => $items->count(),
        ]);
    }

    /**
     * GET /master-data/items/{item}
     * Detail satu item (untuk referensi)
     */
    public function showItem(Request $request, Item $item)
    {
        return new ItemResource($item->load(['category', 'location', 'department', 'creator']));
    }

    /**
     * GET /master-data/locations
     */
    public function getLocations(Request $request)
    {
        $query = Location::query()->where('is_active', true);

        if ($type = $request->query('type')) {
            $query->where('location_type', $type);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('location_code', 'LIKE', "%{$search}%")
                    ->orWhere('location_name', 'LIKE', "%{$search}%");
            });
        }

        $limit = $request->query('limit', 100);
        $locations = $query->orderBy('location_name')->limit($limit)->get();

        return response()->json(['data' => LocationResource::collection($locations)]);
    }

    /**
     * GET /master-data/departments
     */
    public function getDepartments(Request $request)
    {
        $query = Department::query()->where('is_active', true);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('dept_code', 'LIKE', "%{$search}%")
                    ->orWhere('dept_name', 'LIKE', "%{$search}%");
            });
        }

        $limit = $request->query('limit', 50);
        $departments = $query->orderBy('dept_name')->limit($limit)->get();

        return response()->json(['data' => DepartmentResource::collection($departments)]);
    }

    /**
     * GET /master-data/categories
     */
    public function getCategories(Request $request)
    {
        $query = ItemCategory::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('category_code', 'LIKE', "%{$search}%")
                    ->orWhere('category_name', 'LIKE', "%{$search}%");
            });
        }

        $limit = $request->query('limit', 50);
        $categories = $query->orderBy('category_name')->limit($limit)->get();

        return response()->json(['data' => ItemCategoryResource::collection($categories)]);
    }

    /**
     * GET /master-data/engineers
     * Untuk dropdown assign insiden
     * Hanya engineer yang aktif
     */
    public function getEngineers(Request $request)
    {
        // Hanya engineer dan supervisor yang bisa akses endpoint ini
        $user = $request->user();
        if (!in_array($user->role, ['engineer', 'supervisor'])) {
            abort(403, 'Unauthorized.');
        }

        $query = User::where('role', 'engineer')
            ->where('is_active', true);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                    ->orWhere('full_name', 'LIKE', "%{$search}%");
            });
        }

        $limit = $request->query('limit', 50);
        $engineers = $query->orderBy('full_name')
            ->limit($limit)
            ->get(['user_id', 'username', 'full_name', 'department']);

        return response()->json(['data' => UserResource::collection($engineers)]);
    }

    /**
     * GET /master-data/statistics (Untuk dashboard)
     * Supervisor only
     */
    public function getStatistics(Request $request)
    {
        if ($request->user()->role !== 'supervisor') {
            abort(403, 'Only supervisor can access statistics.');
        }

        $stats = [
            'items' => [
                'total' => Item::count(),
                'critical' => Item::where('is_critical', true)->count(),
                'calibration_due' => Item::where('status', 'calibration_due')->count(),
            ],
            'locations' => Location::count(),
            'departments' => Department::count(),
        ];

        return response()->json($stats);
    }

    private function assertSupervisor(Request $request): void
    {
        if ($request->user()->role !== 'supervisor') {
            abort(403, 'Only supervisor can manage master data.');
        }
    }

    private function definitionFor(string $type): array
    {
        $definitions = $this->definitions();

        if (!isset($definitions[$type])) {
            abort(404, 'Unknown master data type.');
        }

        return $definitions[$type];
    }

    private function viewForType(string $type): string
    {
        return match ($type) {
            'items' => 'MasterData/Items',
            'locations' => 'MasterData/Locations',
            'departments' => 'MasterData/Departments',
            'categories' => 'MasterData/Categories',
            default => abort(404, 'Unknown master data type.'),
        };
    }

    private function definitions(): array
    {
        return [
            'items' => [
                'label' => 'Items',
                'description' => 'Manage assets, components, and operational items.',
                'model' => Item::class,
                'order_by' => 'item_name',
                'active_column' => 'is_active',
                'with' => ['category', 'location', 'department'],
                'search_columns' => ['asset_tag', 'item_name', 'serial_number', 'brand', 'model'],
                'columns' => [
                    ['key' => 'asset_tag', 'label' => 'Asset Tag'],
                    ['key' => 'item_name', 'label' => 'Name'],
                    ['key' => 'item_type', 'label' => 'Type'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'location_name', 'label' => 'Location'],
                    ['key' => 'department_name', 'label' => 'Department'],
                    ['key' => 'is_active', 'label' => 'Active'],
                ],
                'fields' => [
                    ['key' => 'asset_tag', 'label' => 'Asset Tag', 'type' => 'text', 'required' => true],
                    ['key' => 'item_name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['key' => 'serial_number', 'label' => 'Serial Number', 'type' => 'text'],
                    ['key' => 'brand', 'label' => 'Brand', 'type' => 'text'],
                    ['key' => 'model', 'label' => 'Model', 'type' => 'text'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['key' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => 'categories'],
                    ['key' => 'item_type', 'label' => 'Item Type', 'type' => 'select', 'options' => 'item_types'],
                    ['key' => 'location_id', 'label' => 'Location', 'type' => 'select', 'options' => 'locations'],
                    ['key' => 'dept_id', 'label' => 'Department', 'type' => 'select', 'options' => 'departments'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => 'item_statuses'],
                    ['key' => 'is_critical', 'label' => 'Critical', 'type' => 'checkbox'],
                    ['key' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ],
            'locations' => [
                'label' => 'Locations',
                'description' => 'Manage locations, lines, stations, and work areas.',
                'model' => Location::class,
                'order_by' => 'location_name',
                'active_column' => 'is_active',
                'with' => [],
                'search_columns' => ['location_code', 'location_name', 'location_type'],
                'columns' => [
                    ['key' => 'location_code', 'label' => 'Code'],
                    ['key' => 'location_name', 'label' => 'Name'],
                    ['key' => 'location_type', 'label' => 'Type'],
                    ['key' => 'is_active', 'label' => 'Active'],
                ],
                'fields' => [
                    ['key' => 'location_code', 'label' => 'Location Code', 'type' => 'text', 'required' => true],
                    ['key' => 'location_name', 'label' => 'Location Name', 'type' => 'text', 'required' => true],
                    ['key' => 'location_type', 'label' => 'Location Type', 'type' => 'select', 'options' => 'location_types'],
                    ['key' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ],
            'departments' => [
                'label' => 'Departments',
                'description' => 'Manage departments and their responsible persons.',
                'model' => Department::class,
                'order_by' => 'dept_name',
                'active_column' => 'is_active',
                'with' => [],
                'search_columns' => ['dept_code', 'dept_name', 'manager_name'],
                'columns' => [
                    ['key' => 'dept_code', 'label' => 'Code'],
                    ['key' => 'dept_name', 'label' => 'Name'],
                    ['key' => 'manager_name', 'label' => 'Manager'],
                    ['key' => 'is_active', 'label' => 'Active'],
                ],
                'fields' => [
                    ['key' => 'dept_code', 'label' => 'Department Code', 'type' => 'text', 'required' => true],
                    ['key' => 'dept_name', 'label' => 'Department Name', 'type' => 'text', 'required' => true],
                    ['key' => 'manager_name', 'label' => 'Manager Name', 'type' => 'text'],
                    ['key' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ],
            'categories' => [
                'label' => 'Categories',
                'description' => 'Manage asset/item categories.',
                'model' => ItemCategory::class,
                'order_by' => 'category_name',
                'active_column' => 'is_active',
                'with' => [],
                'search_columns' => ['category_code', 'category_name', 'description'],
                'columns' => [
                    ['key' => 'category_code', 'label' => 'Code'],
                    ['key' => 'category_name', 'label' => 'Name'],
                    ['key' => 'description', 'label' => 'Description'],
                    ['key' => 'is_active', 'label' => 'Active'],
                ],
                'fields' => [
                    ['key' => 'category_code', 'label' => 'Category Code', 'type' => 'text', 'required' => true],
                    ['key' => 'category_name', 'label' => 'Category Name', 'type' => 'text', 'required' => true],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['key' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ],
        ];
    }

    private function typeCards(): array
    {
        return collect($this->definitions())
            ->map(function ($definition, $type) {
                $model = $definition['model'];

                return [
                    'type' => $type,
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'count' => $model::count(),
                    'inactive' => $model::where('is_active', false)->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function formOptions(): array
    {
        return [
            'categories' => ItemCategory::query()
                ->select(['category_id', 'category_name'])
                ->orderBy('category_name')
                ->get()
                ->map(fn ($row) => ['value' => $row->category_id, 'label' => $row->category_name])
                ->all(),
            'locations' => Location::query()
                ->select(['location_id', 'location_name'])
                ->orderBy('location_name')
                ->get()
                ->map(fn ($row) => ['value' => $row->location_id, 'label' => $row->location_name])
                ->all(),
            'departments' => Department::query()
                ->select(['dept_id', 'dept_name'])
                ->orderBy('dept_name')
                ->get()
                ->map(fn ($row) => ['value' => $row->dept_id, 'label' => $row->dept_name])
                ->all(),
            'item_types' => [
                ['value' => 'machine', 'label' => 'Machine'],
                ['value' => 'equipment', 'label' => 'Equipment'],
                ['value' => 'tool', 'label' => 'Tool'],
                ['value' => 'component', 'label' => 'Component'],
                ['value' => 'sensor', 'label' => 'Sensor'],
                ['value' => 'vehicle', 'label' => 'Vehicle'],
            ],
            'item_statuses' => [
                ['value' => 'operational', 'label' => 'Operational'],
                ['value' => 'maintenance', 'label' => 'Maintenance'],
                ['value' => 'broken', 'label' => 'Broken'],
                ['value' => 'retired', 'label' => 'Retired'],
                ['value' => 'calibration_due', 'label' => 'Calibration Due'],
            ],
            'location_types' => [
                ['value' => 'building', 'label' => 'Building'],
                ['value' => 'floor', 'label' => 'Floor'],
                ['value' => 'line', 'label' => 'Line'],
                ['value' => 'cell', 'label' => 'Cell'],
                ['value' => 'station', 'label' => 'Station'],
            ],
        ];
    }

    private function rulesFor(string $type, $record = null): array
    {
        return match ($type) {
            'items' => [
                'asset_tag' => ['required', 'string', 'max:50', Rule::unique('items', 'asset_tag')->ignore($record?->item_id ?? null, 'item_id')],
                'item_name' => ['required', 'string', 'max:200'],
                'serial_number' => ['nullable', 'string', 'max:100'],
                'brand' => ['nullable', 'string', 'max:100'],
                'model' => ['nullable', 'string', 'max:100'],
                'description' => ['nullable', 'string'],
                'category_id' => ['nullable', 'integer', 'exists:item_categories,category_id'],
                'item_type' => ['nullable', Rule::in(['machine', 'equipment', 'tool', 'component', 'sensor', 'vehicle'])],
                'location_id' => ['nullable', 'integer', 'exists:locations,location_id'],
                'dept_id' => ['nullable', 'integer', 'exists:departments,dept_id'],
                'status' => ['required', Rule::in(['operational', 'maintenance', 'broken', 'retired', 'calibration_due'])],
                'is_critical' => ['sometimes', 'boolean'],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'locations' => [
                'location_code' => ['required', 'string', 'max:30', Rule::unique('locations', 'location_code')->ignore($record?->location_id ?? null, 'location_id')],
                'location_name' => ['required', 'string', 'max:100'],
                'location_type' => ['required', Rule::in(['building', 'floor', 'line', 'cell', 'station'])],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'departments' => [
                'dept_code' => ['required', 'string', 'max:20', Rule::unique('departments', 'dept_code')->ignore($record?->dept_id ?? null, 'dept_id')],
                'dept_name' => ['required', 'string', 'max:100'],
                'manager_name' => ['nullable', 'string', 'max:100'],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'categories' => [
                'category_code' => ['required', 'string', 'max:30', Rule::unique('item_categories', 'category_code')->ignore($record?->category_id ?? null, 'category_id')],
                'category_name' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string'],
                'is_active' => ['sometimes', 'boolean'],
            ],
            default => abort(404, 'Unknown master data type.'),
        };
    }

    private function mapPayload(string $type, array $data): array
    {
        return match ($type) {
            'items' => [
                'asset_tag' => $data['asset_tag'],
                'item_name' => $data['item_name'],
                'serial_number' => $data['serial_number'] ?? null,
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'item_type' => $data['item_type'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'dept_id' => $data['dept_id'] ?? null,
                'status' => $data['status'],
                'is_critical' => (bool) ($data['is_critical'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ],
            'locations' => [
                'location_code' => $data['location_code'],
                'location_name' => $data['location_name'],
                'location_type' => $data['location_type'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ],
            'departments' => [
                'dept_code' => $data['dept_code'],
                'dept_name' => $data['dept_name'],
                'manager_name' => $data['manager_name'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ],
            'categories' => [
                'category_code' => $data['category_code'],
                'category_name' => $data['category_name'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ],
            default => abort(404, 'Unknown master data type.'),
        };
    }

    private function formatRecord(string $type, $record): array
    {
        return match ($type) {
            'items' => [
                'id' => $record->item_id,
                'asset_tag' => $record->asset_tag,
                'item_name' => $record->item_name,
                'serial_number' => $record->serial_number,
                'brand' => $record->brand,
                'model' => $record->model,
                'description' => $record->description,
                'item_type' => $record->item_type,
                'status' => $record->status,
                'is_active' => (bool) $record->is_active,
                'is_critical' => (bool) $record->is_critical,
                'category_id' => $record->category_id,
                'category_name' => $record->category?->category_name,
                'location_id' => $record->location_id,
                'location_name' => $record->location?->location_name,
                'dept_id' => $record->dept_id,
                'department_name' => $record->department?->dept_name,
            ],
            'locations' => [
                'id' => $record->location_id,
                'location_code' => $record->location_code,
                'location_name' => $record->location_name,
                'location_type' => $record->location_type,
                'is_active' => (bool) $record->is_active,
            ],
            'departments' => [
                'id' => $record->dept_id,
                'dept_code' => $record->dept_code,
                'dept_name' => $record->dept_name,
                'manager_name' => $record->manager_name,
                'is_active' => (bool) $record->is_active,
            ],
            'categories' => [
                'id' => $record->category_id,
                'category_code' => $record->category_code,
                'category_name' => $record->category_name,
                'description' => $record->description,
                'is_active' => (bool) $record->is_active,
            ],
            default => abort(404, 'Unknown master data type.'),
        };
    }
}