<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'category_id' => $this->category_id,
            'name' => $this->name,
            'is_active' => $this->is_active ?? true,
        ];
    }
}
