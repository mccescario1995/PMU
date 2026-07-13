<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_name',
        'category',
        'quantity',
        'unit',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }
}
