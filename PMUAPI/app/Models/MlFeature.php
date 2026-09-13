<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MlFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'year_num',
        'month_num',
        'day_num',
        'day_of_week',
        'quarter_num',
        'is_weekend',
        'is_month_start',
        'is_month_end',
        'revenue_target',
        'log_revenue',
        'revenue_lag_1d',
        'revenue_lag_7d',
        'revenue_lag_365d',
        'revenue_rolling_7d_mean',
        'revenue_rolling_30d_mean',
        'summary_metric_col17',
        'is_missing_date',
    ];

    protected $casts = [
        'report_date' => 'date',
        'year_num' => 'integer',
        'month_num' => 'integer',
        'day_num' => 'integer',
        'day_of_week' => 'integer',
        'quarter_num' => 'integer',
        'is_weekend' => 'boolean',
        'is_month_start' => 'boolean',
        'is_month_end' => 'boolean',
        'revenue_target' => 'decimal:2',
        'log_revenue' => 'decimal:6',
        'revenue_lag_1d' => 'decimal:2',
        'revenue_lag_7d' => 'decimal:2',
        'revenue_lag_365d' => 'decimal:2',
        'revenue_rolling_7d_mean' => 'decimal:2',
        'revenue_rolling_30d_mean' => 'decimal:2',
        'summary_metric_col17' => 'decimal:2',
        'is_missing_date' => 'boolean',
    ];
}
