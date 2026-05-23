<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'incident_id' => $this->incident_id,
            'incident_code' => $this->incident_code,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'severity' => $this->severity,
            'priority' => $this->priority ?? null,
            'investigation_notes' => $this->investigation_notes,
            'root_cause_hypothesis' => $this->root_cause_hypothesis,
            'hypothesis_review_notes' => $this->hypothesis_review_notes,
            'hypothesis_approved' => $this->hypothesis_approved,
            'hypothesis_approved_at' => optional($this->hypothesis_approved_at)->toDateTimeString(),
            'corrective_actions' => $this->corrective_actions,
            'parts_replaced' => $this->parts_replaced,
            'repair_started_at' => optional($this->repair_started_at)->toDateTimeString(),
            'resolved_at' => optional($this->resolved_at)->toDateTimeString(),
            'verification_notes' => $this->verification_notes,
            'verified_at' => optional($this->verified_at)->toDateTimeString(),
            'investigating_started_at' => optional($this->investigating_started_at)->toDateTimeString(),
            'closing_requested' => (int) ($this->closing_requests_count ?? 0) > 0,
            'item' => $this->whenLoaded('item', function () {
                return [
                    'item_id' => $this->item?->item_id,
                    'name' => $this->item?->name ?? $this->item?->item_name ?? null,
                ];
            }),
            'location' => $this->whenLoaded('location', function () {
                return [
                    'location_id' => $this->location?->location_id,
                    'name' => $this->location?->location_name ?? null,
                ];
            }),
            'reported_by' => $this->whenLoaded('reporter', function () {
                return [
                    'id' => $this->reporter?->id,
                    'name' => $this->reporter?->name,
                ];
            }),
            'assigned_to' => $this->whenLoaded('handler', function () {
                return $this->handler ? ['id' => $this->handler->id, 'name' => $this->handler->name] : null;
            }),
            'verified_by' => $this->whenLoaded('verifier', function () {
                return $this->verifier ? ['id' => $this->verifier->id, 'name' => $this->verifier->name] : null;
            }),
            'closed_by' => $this->whenLoaded('closer', function () {
                return $this->closer ? ['id' => $this->closer->id, 'name' => $this->closer->name] : null;
            }),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'detected_at' => optional($this->detected_at)->toDateTimeString(),
            'resolved_at' => optional($this->resolved_at)->toDateTimeString(),
            'closed_at' => optional($this->closed_at)->toDateTimeString(),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'audit_logs' => AuditLogResource::collection($this->whenLoaded('auditLogs')),
            'can' => [
                'view' => isset($request->user) ? $request->user()->can('view', $this->resource) : false,
                'update' => isset($request->user) ? $request->user()->can('update', $this->resource) : false,
                'assign' => isset($request->user) ? $request->user()->can('assign', $this->resource) : false,
                'approve' => isset($request->user) ? $request->user()->can('approve', $this->resource) : false,
            ],
        ];
    }
}
