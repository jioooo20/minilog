<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->user_id ?? $this->id,
            'name' => $this->full_name ?? $this->name,
            'username' => $this->username ?? null,
            'email' => $this->email ?? null,
            'role' => $this->role ?? null,
        ];
    }
}
