<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\AuditLog;
use App\Models\Item;
use App\Http\Resources\IncidentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\AuditLogResource;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:supervisor');
    }

    /**
     * Daftar insiden closed untuk audit index
     */
    public function index(Request $request)
    {
        $query = Incident::query()
            ->where('status', 'closed')
            ->with([
                'item',
                'location',
                'reporter',
                'handler',
                'approver',
                'verifier',
                'closer',
            ])
            ->withCount([
                'auditLogs as closing_requests_count' => function ($relation) {
                    $relation->where('action', 'request_closing');
                },
                'auditLogs as audit_logs_count',
            ])
            ->orderByDesc('closed_at')
            ->orderByDesc('resolved_at')
            ->orderByDesc('created_at');

        if ($severity = $request->query('severity')) {
            $query->where('severity', $severity);
        }

        if ($itemId = $request->query('item_id')) {
            $query->where('item_id', $itemId);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('closed_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('closed_at', '<=', $dateTo);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('incident_code', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('closing_notes', 'LIKE', "%{$search}%");
            });
        }

        $incidents = $query->paginate(9)->withQueryString();

        $stats = [
            'total_closed' => Incident::where('status', 'closed')->count(),
            'this_month' => Incident::where('status', 'closed')
                ->whereMonth('closed_at', now()->month)
                ->whereYear('closed_at', now()->year)
                ->count(),
            'critical_closed' => Incident::where('status', 'closed')->where('severity', 'critical')->count(),
            'avg_closing_days' => Incident::where('status', 'closed')
                ->whereNotNull('detected_at')
                ->whereNotNull('closed_at')
                ->get()
                ->avg(function ($incident) {
                    return $incident->detected_at->diffInDays($incident->closed_at);
                }),
        ];

        // timeseries: last 6 months closed counts
        $timeseries = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->copy()->subMonths($i);
            $label = $date->format('M Y');
            $count = Incident::where('status', 'closed')
                ->whereYear('closed_at', $date->year)
                ->whereMonth('closed_at', $date->month)
                ->count();
            $timeseries[] = ['label' => $label, 'count' => $count];
        }

        $payload = [
            'incidents' => IncidentResource::collection($incidents)->response()->getData(true),
            'items' => Item::query()->select(['item_id', 'item_name'])->orderBy('item_name')->get()->toArray(),
            'filters' => [
                'severity' => $request->query('severity', ''),
                'item_id' => $request->query('item_id', ''),
                'date_from' => $request->query('date_from', ''),
                'date_to' => $request->query('date_to', ''),
                'search' => $request->query('search', ''),
            ],
            'stats' => $stats,
            'timeseries' => $timeseries,
        ];

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return Inertia::render('Audit/Index', $payload);
    }

    /**
     * Audit trail spesifik untuk satu insiden
     */
    public function show(Incident $incident)
    {
        $auditLogs = $incident->auditLogs()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        // Timeline grouping berdasarkan jam/menit
        $timeline = $auditLogs->map(function ($log) {
            return [
                'time' => $log->created_at->format('H:i:s'),
                'full_datetime' => $log->created_at->toDateTimeString(),
                'user_name' => $log->user->full_name,
                'user_role' => $log->user->role,
                'action' => $log->action,
                'details' => $log->action_details,
                'ip_address' => $log->ip_address,
            ];
        });

        // Ringkasan durasi tiap fase (jika ada data timestamp dari incident)
        $phases = $this->calculatePhaseDurations($incident);

        return response()->json([
            'data' => AuditLogResource::collection($auditLogs),
            'timeline' => $timeline,
            'phases' => $phases,
            'total_logs' => $auditLogs->count(),
        ]);
    }

    /**
     * Export audit logs ke CSV
     */
    public function export(Request $request)
    {
        $query = AuditLog::query()
            ->with(['user', 'incident'])
            ->orderByDesc('created_at');

        // Apply filters sama seperti index
        if ($incidentId = $request->query('incident_id')) {
            $query->where('incident_id', $incidentId);
        }
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->get();

        // Generate CSV
        $fileName = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Log ID', 'Incident Code', 'Username', 'Role', 'Action', 'Details', 'IP Address', 'Timestamp']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->log_id,
                    $log->incident->incident_code ?? 'N/A',
                    $log->user->username ?? 'N/A',
                    $log->user->role ?? 'N/A',
                    $log->action,
                    $log->action_details,
                    $log->ip_address,
                    $log->created_at,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Hitung durasi setiap fase insiden
     */
    private function calculatePhaseDurations(Incident $incident): array
    {
        $phases = [];

        if ($incident->detected_at) {
            $phases['detection_to_investigation'] = $incident->investigating_started_at
                ? $incident->detected_at->diffInMinutes($incident->investigating_started_at) . ' menit'
                : 'Belum mulai';
        }

        if ($incident->investigating_started_at && $incident->hypothesis_approved_at) {
            $phases['investigation_duration'] = $incident->investigating_started_at
                ->diffInMinutes($incident->hypothesis_approved_at) . ' menit';
        }

        if ($incident->hypothesis_approved_at && $incident->resolved_at) {
            $phases['repair_duration'] = $incident->hypothesis_approved_at
                ->diffInMinutes($incident->resolved_at) . ' menit';
        }

        if ($incident->resolved_at && $incident->verified_at) {
            $phases['verification_duration'] = $incident->resolved_at
                ->diffInMinutes($incident->verified_at) . ' menit';
        }

        if ($incident->detected_at && $incident->closed_at) {
            $phases['total_duration'] = $incident->detected_at
                ->diffInMinutes($incident->closed_at) . ' menit';
        }

        return $phases;
    }
}