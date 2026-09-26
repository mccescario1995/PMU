<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stakeholder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'official_receipt',
        'stakeholder_type_id',
        'status',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function stakeholderType(): BelongsTo
    {
        return $this->belongsTo(StakeholderType::class, 'stakeholder_type_id');
    }
}
