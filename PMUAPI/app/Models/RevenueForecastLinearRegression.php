<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevenueForecastLinearRegression extends Model
{
    use HasFactory;

    protected $table = 'revenue_forecasts_linear_regression';

    protected $fillable = [
        'forecast_date',
        'predicted_revenue',
        'season',
        'model_version',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_revenue' => 'decimal:2',
    ];
}