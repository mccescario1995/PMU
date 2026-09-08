<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StakeholderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'stakeholder_type_id' => $this->stakeholder_type_id,
            'stakeholder_type' => $this->stakeholderType ? [
                'id' => $this->stakeholderType->id,
                'name' => $this->stakeholderType->name,
                'description' => $this->stakeholderType->description,
            ] : null,
            'contact_no' => $this->contact_no,
            'email' => $this->email,
            'address' => $this->address,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
