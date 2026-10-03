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
        'category_type',
        'quantity',
        'unit',
        'status',
        'minimum_stock',
        'reorder_quantity',
        'average_daily_usage',
        'price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'minimum_stock' => 'integer',
        'reorder_quantity' => 'integer',
        'average_daily_usage' => 'float',
        'price' => 'decimal:2',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }
}
