<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'notif_id' => $this->notif_id,
            'type' => $this->type,
            'message' => $this->message,
            'is_read' => (bool) ($this->is_read ?? false),
            'incident' => $this->whenLoaded('incident', function () {
                return [
                    'incident_id' => $this->incident?->incident_id,
                    'title' => $this->incident?->title,
                    'status' => $this->incident?->status,
                    'verification_notes' => $this->incident?->verification_notes,
                    'verified_at' => optional($this->incident?->verified_at)->toDateTimeString(),
                    'repair_started_at' => optional($this->incident?->repair_started_at)->toDateTimeString(),
                    'resolved_at' => optional($this->incident?->resolved_at)->toDateTimeString(),
                ];
            }),
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
