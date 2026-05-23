<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Incident;
use App\Http\Resources\IncidentResource;

class dashboardController extends Controller
{
    public function operatorData(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'operator') {
            abort(403, 'Only operators can access this endpoint.');
        }

        $baseQuery = Incident::query()->where('reported_by', $user->id);

        $totalReported = (clone $baseQuery)->count();
        $openReported = (clone $baseQuery)->where('status', '!=', 'closed')->count();
        $closedReported = (clone $baseQuery)->where('status', 'closed')->count();
        $pendingVerification = (clone $baseQuery)
            ->where('status', 'verifying')
            ->whereHas('auditLogs', function ($query) {
                $query->where('action', 'request_closing');
            })
            ->count();

        $awaitingClosureRequest = (clone $baseQuery)
            ->where('status', 'verifying')
            ->whereDoesntHave('auditLogs', function ($query) {
                $query->where('action', 'request_closing');
            })
            ->count();

        $timeseries = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->copy()->subMonths($i);
            $timeseries[] = [
                'label' => $date->format('M Y'),
                'count' => Incident::where('reported_by', $user->id)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        }

        $recent = (clone $baseQuery)
            ->with(['item', 'location', 'handler', 'verifier', 'closer'])
            ->withCount([
                'auditLogs as closing_requests_count' => function ($query) {
                    $query->where('action', 'request_closing');
                },
            ])
            ->orderByDesc('detected_at')
            ->limit(6)
            ->get();

        return response()->json([
            'stats' => [
                'total_reported' => $totalReported,
                'open_reported' => $openReported,
                'closed_reported' => $closedReported,
                'pending_verification' => $pendingVerification,
                'awaiting_closure_request' => $awaitingClosureRequest,
            ],
            'timeseries' => $timeseries,
            'recent' => IncidentResource::collection($recent)->response()->getData(true),
        ]);
    }

    public function engineerData(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'engineer') {
            abort(403, 'Only engineers can access this endpoint.');
        }

        $totalAssigned = Incident::where('handled_by', $user->id)->count();
        $openAssigned = Incident::where('handled_by', $user->id)->where('status', '!=', 'closed')->count();
        $criticalAssigned = Incident::where('handled_by', $user->id)->where('severity', 'critical')->count();
        $pendingRepair = Incident::where('handled_by', $user->id)->where('status', 'repairing')->count();

        $recent = Incident::where('handled_by', $user->id)
            ->with(['item', 'location'])
            ->orderByDesc('detected_at')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                'total_assigned' => $totalAssigned,
                'open_assigned' => $openAssigned,
                'critical_assigned' => $criticalAssigned,
                'pending_repair' => $pendingRepair,
            ],
            'recent' => IncidentResource::collection($recent)->response()->getData(true),
        ]);
    }
}