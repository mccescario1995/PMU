<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'stakeholder' => new StakeholderResource($this->whenLoaded('stakeholder')),
            'stakeholder_id' => $this->stakeholder_id,
            'total_amount' => $this->total_amount,
            'transaction_date' => $this->transaction_date,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'recorded_by' => $this->recorded_by,
            'recorded_by_name' => $this->whenLoaded('recordedBy', fn () => $this->recordedBy?->name),
            'items' => TransactionItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
