<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'log_id' => $this->log_id,
            'incident_id' => $this->incident_id,
            'action' => $this->action,
            'action_details' => $this->action_details,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'ip_address' => $this->ip_address,
            'performed_by' => $this->whenLoaded('user', function () {
                return ['id' => $this->user?->id, 'name' => $this->user?->name];
            }),
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
