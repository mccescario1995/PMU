<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'profile_picture' => $this->profile_picture
                ? Storage::disk('public')->url($this->profile_picture)
                : null,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()->all()),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')->values()->all()),
            'all_permissions' => $this->getAllPermissions()->pluck('name')->values()->all(),
            'created_at' => $this->created_at,
        ];
    }
}