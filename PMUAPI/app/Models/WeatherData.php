<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherData extends Model
{
    use HasFactory;

    protected $fillable = [
        'weather_date',
        'rainfall_mm',
        'wind_speed',
        'temperature',
    ];

    protected $casts = [
        'weather_date' => 'date',
        'rainfall_mm' => 'decimal:2',
        'wind_speed' => 'decimal:2',
        'temperature' => 'decimal:2',
    ];
}
