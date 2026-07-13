<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FeeTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'fee_name' => $this->fee_name,
            'base_rate' => $this->base_rate,
            'unit' => $this->unit,
            'created_at' => $this->created_at,
        ];
    }
}
