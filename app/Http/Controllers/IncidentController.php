<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentStoreRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Models\Item;
use App\Models\Location;
use App\Services\IncidentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IncidentController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:operator')->only(['create', 'store']);
        $this->middleware('role:operator|engineer|supervisor')->only(['index', 'show']);
    }

    public function create()
    {
        return Inertia::render('Incidents/Create', [
            'items' => Item::query()
                ->select(['item_id', 'item_name'])
                ->orderBy('item_name')
                ->get()
                ->toArray(),
            'locations' => Location::query()
                ->select(['location_id', 'location_name'])
                ->where('is_active', true)
                ->orderBy('location_name')
                ->get()
                ->toArray(),
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Incident::query()->with([
            'item',
            'location',
            'reporter',
            'handler',
        ])->withCount([
            'auditLogs as closing_requests_count' => function ($relation) {
                $relation->where('action', 'request_closing');
            },
        ]);

        if ($user->role === 'operator') {
            $query->where(function ($builder) use ($user) {
                $builder
                    ->where('reported_by', $user->id)
                    ->orWhere('status', 'verifying');
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($severity = $request->query('severity')) {
            $query->where('severity', $severity);
        }

        if ($itemId = $request->query('item_id')) {
            $query->where('item_id', $itemId);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('detected_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('detected_at', '<=', $dateTo);
        }

        $incidents = $query
            ->orderByDesc('detected_at')
            ->paginate(10)
            ->withQueryString();

        if ($request->wantsJson()) {
            return IncidentResource::collection($incidents);
        }

        return Inertia::render('Incidents/Index', [
            'incidents' => IncidentResource::collection($incidents)->response()->getData(true),
            'filters' => [
                'status' => $request->query('status', ''),
                'severity' => $request->query('severity', ''),
                'item_id' => $request->query('item_id', ''),
                'date_from' => $request->query('date_from', ''),
                'date_to' => $request->query('date_to', ''),
            ],
            'items' => Item::query()
                ->select(['item_id', 'item_name'])
                ->orderBy('item_name')
                ->get()
                ->toArray(),
        ]);
    }

    public function show(Request $request, Incident $incident)
    {
        $user = $request->user();

        if ($user->role === 'operator') {
            if ($incident->reported_by !== $user->id && $incident->status !== 'verifying') {
                abort(403, 'Not allowed to view this incident.');
            }
        }

        if ($user->role === 'engineer' && $incident->handled_by !== $user->id) {
            abort(403, 'Not allowed to view this incident.');
        }

        $incident->load([
            'item',
            'componentItem',
            'location',
            'reporter',
            'handler',
            'approver',
            'verifier',
            'attachments',
            'auditLogs' => function ($q) {
                $q->orderByDesc('created_at')->with('user');
            },
        ]);

        $incident->loadCount([
            'auditLogs as closing_requests_count' => function ($query) {
                $query->where('action', 'request_closing');
            },
        ]);

        $resource = new IncidentResource($incident);

        if ($request->wantsJson()) {
            return $resource;
        }

        return Inertia::render('Incidents/Show', [
            'incident' => $resource->response()->getData(true),
            // dd($incident->toArray()),
        ]);
    }

    public function store(IncidentStoreRequest $request, IncidentService $service)
    {
        $incident = $service->createIncident(
            $request->validated(),
            $request->user(),
            $request->ip()
        );

        if ($request->wantsJson()) {
            return (new IncidentResource($incident->load(['item', 'location'])))->response()->setStatusCode(201);
        }

        return redirect()
            ->route('incidents.show', $incident->incident_id)
            ->with('success', 'Insiden berhasil dibuat.');
    }
}
