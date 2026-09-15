<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionRevenueFeature extends Model
{
    use HasFactory;

    protected $table = 'transaction_revenue_features';

    protected $fillable = [
        'report_date',
        'revenue_lag_1d',
        'revenue_lag_7d',
        'revenue_lag_365d',
        'revenue_rolling_7d_mean',
        'revenue_rolling_30d_mean',
    ];

    protected $casts = [
        'report_date' => 'date',
        'revenue_lag_1d' => 'decimal:2',
        'revenue_lag_7d' => 'decimal:2',
        'revenue_lag_365d' => 'decimal:2',
        'revenue_rolling_7d_mean' => 'decimal:2',
        'revenue_rolling_30d_mean' => 'decimal:2',
    ];
}