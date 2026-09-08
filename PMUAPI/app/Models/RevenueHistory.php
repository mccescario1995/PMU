<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevenueHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'revenue_date',
        'total_revenue',
        'transaction_count',
    ];

    protected $casts = [
        'revenue_date' => 'date',
        'total_revenue' => 'decimal:2',
        'transaction_count' => 'integer',
    ];
}
