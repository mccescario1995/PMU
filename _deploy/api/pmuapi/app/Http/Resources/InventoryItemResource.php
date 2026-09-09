<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'item_name' => $this->item_name,
            'category' => $this->category,
            'category_type' => $this->category_type,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
