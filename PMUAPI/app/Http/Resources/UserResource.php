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
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()->all()),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')->values()->all()),
            'all_permissions' => $this->getAllPermissions()->pluck('name')->values()->all(),
            'created_at' => $this->created_at,
        ];
    }
}
