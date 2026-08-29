<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevenueForecast extends Model
{
    use HasFactory;

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

    public function getSeasonFromDateAttribute(): string
    {
        $month = (int) $this->forecast_date->format('n');

        return $month >= 1 && $month <= 6 ? 'Peak' : 'Off-Peak';
    }
}
