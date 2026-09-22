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
            'minimum_stock' => $this->minimum_stock,
            'reorder_quantity' => $this->reorder_quantity,
            'average_daily_usage' => (float) $this->average_daily_usage,
            'status' => $this->status,
            'stock_status' => $this->status === 'damaged'
                ? 'damaged'
                : ($this->quantity <= $this->minimum_stock ? 'low_stock' : 'available'),
            'days_remaining' => $this->average_daily_usage > 0
                ? (float) ($this->quantity / $this->average_daily_usage)
                : null,
            'created_at' => $this->created_at,
        ];
    }
}
