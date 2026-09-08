<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'stakeholder_id',
        'total_amount',
        'transaction_date',
        'recorded_by',
        'status',
        'remarks',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function stakeholder(): BelongsTo
    {
        return $this->belongsTo(Stakeholder::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}
