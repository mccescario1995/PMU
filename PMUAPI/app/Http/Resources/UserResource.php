<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'role' => new RoleResource($this->whenLoaded('role')),
            'role_id' => $this->role_id,
            'created_at' => $this->created_at,
        ];
    }
}
