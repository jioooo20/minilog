<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'dept_id' => $this->dept_id,
            'name' => $this->name,
        ];
    }
}
