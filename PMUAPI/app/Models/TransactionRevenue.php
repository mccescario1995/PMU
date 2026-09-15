<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionRevenue extends Model
{
    use HasFactory;

    protected $table = 'transaction_revenue';

    protected $fillable = [
        'report_date',
        'revenue_target',
        'log_revenue',
        'temp_celsius',
        'precipitation_mm',
        'wind_speed',
        'year_num',
        'month_num',
        'day_num',
        'day_of_week',
        'quarter_num',
        'is_weekend',
        'is_month_start',
        'is_month_end',
    ];

    protected $casts = [
        'report_date' => 'date',
        'revenue_target' => 'decimal:2',
        'log_revenue' => 'decimal:6',
        'temp_celsius' => 'decimal:2',
        'precipitation_mm' => 'decimal:2',
        'wind_speed' => 'decimal:2',
        'is_weekend' => 'boolean',
        'is_month_start' => 'boolean',
        'is_month_end' => 'boolean',
    ];
}