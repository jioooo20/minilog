<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'attachment_id' => $this->attachment_id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'description' => $this->description,
            'source' => $this->source,
            'uploaded_at' => optional($this->uploaded_at)->toDateTimeString(),
            'uploaded_by' => $this->whenLoaded('uploader', function () {
                return ['id' => $this->uploader?->id, 'name' => $this->uploader?->name];
            }),
        ];
    }
}
