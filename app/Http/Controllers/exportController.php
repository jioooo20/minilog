<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class exportController extends Controller
{
    public function finalReport(Request $request, Incident $incident)
    {
        $user = $request->user();

        if ($user->role !== 'supervisor') {
            abort(403, 'Only supervisors can export the final report.');
        }

        $incident->load([
            'item',
            'componentItem',
            'location',
            'reporter',
            'handler',
            'approver',
            'verifier',
            'closer',
            'auditLogs' => function ($query) {
                $query->orderBy('created_at', 'asc')->with('user');
            },
        ]);

        $incident->loadCount([
            'auditLogs as closing_requests_count' => function ($query) {
                $query->where('action', 'request_closing');
            },
        ]);

        $data = [
            'reportTitle' => 'Laporan Final Audit Insiden',
            'reportNumber' => 'RPT-' . $incident->incident_code . '-' . now()->format('YmdHis'),
            'generatedAt' => now(),
            'preparedBy' => $user,
            'incident' => [
                'incident_id' => $incident->incident_id,
                'incident_code' => $incident->incident_code,
                'title' => $incident->title,
                'description' => $incident->description,
                'status' => $incident->status,
                'severity' => $incident->severity,
                'priority' => $incident->priority,
                'investigation_notes' => $incident->investigation_notes,
                'root_cause_hypothesis' => $incident->root_cause_hypothesis,
                'hypothesis_review_notes' => $incident->hypothesis_review_notes,
                'hypothesis_approved' => (bool) $incident->hypothesis_approved,
                'corrective_actions' => $incident->corrective_actions,
                'parts_replaced' => $incident->parts_replaced,
                'verification_notes' => $incident->verification_notes,
                'closing_notes' => $incident->closing_notes,
                'closing_requested' => (int) ($incident->closing_requests_count ?? 0) > 0,
                'detected_at' => optional($incident->detected_at)->toDateTimeString(),
                'investigating_started_at' => optional($incident->investigating_started_at)->toDateTimeString(),
                'repair_started_at' => optional($incident->repair_started_at)->toDateTimeString(),
                'hypothesis_approved_at' => optional($incident->hypothesis_approved_at)->toDateTimeString(),
                'resolved_at' => optional($incident->resolved_at)->toDateTimeString(),
                'verified_at' => optional($incident->verified_at)->toDateTimeString(),
                'closed_at' => optional($incident->closed_at)->toDateTimeString(),
                'item' => [
                    'item_id' => $incident->item?->item_id,
                    'name' => $incident->item?->name ?? $incident->item?->item_name ?? null,
                ],
                'component_item' => [
                    'item_id' => $incident->componentItem?->item_id,
                    'name' => $incident->componentItem?->name ?? $incident->componentItem?->item_name ?? null,
                ],
                'location' => [
                    'location_id' => $incident->location?->location_id,
                    'name' => $incident->location?->location_name ?? null,
                ],
                'reported_by' => [
                    'id' => $incident->reporter?->id,
                    'name' => $incident->reporter?->name,
                ],
                'assigned_to' => $incident->handler ? [
                    'id' => $incident->handler->id,
                    'name' => $incident->handler->name,
                ] : null,
                'approved_by' => $incident->approver ? [
                    'id' => $incident->approver->id,
                    'name' => $incident->approver->name,
                ] : null,
                'verified_by' => $incident->verifier ? [
                    'id' => $incident->verifier->id,
                    'name' => $incident->verifier->name,
                ] : null,
                'closed_by' => $incident->closer ? [
                    'id' => $incident->closer->id,
                    'name' => $incident->closer->name,
                ] : null,
            ],
            'phases' => $this->calculatePhaseDurations($incident),
            'timeline' => $incident->auditLogs->map(function ($log) {
                return [
                    'time' => optional($log->created_at)->format('d M Y, H:i:s'),
                    'action' => $log->action,
                    'action_label' => str_replace('_', ' ', $log->action),
                    'details' => $log->action_details,
                    'performed_by' => $log->user?->name,
                    'role' => $log->user?->role,
                    'ip_address' => $log->ip_address,
                    'old_value' => $log->old_value,
                    'new_value' => $log->new_value,
                ];
            })->all(),
        ];

        $pdf = Pdf::loadView('exports.final-report', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        return $pdf->download('final-report-' . $incident->incident_code . '.pdf');
    }

    private function calculatePhaseDurations(Incident $incident): array
    {
        $phases = [];

        if ($incident->detected_at && $incident->investigating_started_at) {
            $phases['Detection to Investigation'] = $incident->detected_at->diffInMinutes($incident->investigating_started_at) . ' menit';
        }

        if ($incident->investigating_started_at && $incident->hypothesis_approved_at) {
            $phases['Investigation Duration'] = $incident->investigating_started_at->diffInMinutes($incident->hypothesis_approved_at) . ' menit';
        }

        if ($incident->hypothesis_approved_at && $incident->resolved_at) {
            $phases['Repair Duration'] = $incident->hypothesis_approved_at->diffInMinutes($incident->resolved_at) . ' menit';
        }

        if ($incident->resolved_at && $incident->verified_at) {
            $phases['Verification Duration'] = $incident->resolved_at->diffInMinutes($incident->verified_at) . ' menit';
        }

        if ($incident->detected_at && $incident->closed_at) {
            $phases['Total Duration'] = $incident->detected_at->diffInMinutes($incident->closed_at) . ' menit';
        }

        return $phases;
    }
}
