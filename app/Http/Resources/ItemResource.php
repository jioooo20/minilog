<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'item_id' => $this->item_id,
            'name' => $this->name ?? $this->item_name ?? null,
            'category' => $this->whenLoaded('category', function () {
                return ['category_id' => $this->category?->category_id, 'name' => $this->category?->name];
            }),
            'is_active' => $this->is_active ?? true,
        ];
    }
}
