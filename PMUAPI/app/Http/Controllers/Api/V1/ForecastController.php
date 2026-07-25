<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RevenueForecast;
use App\Models\WeatherData;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    public function index()
    {
        $forecasts = RevenueForecast::orderBy('forecast_date')->get();

        $dates = $forecasts->pluck('forecast_date')->map(fn ($d) => $d->toDateString())->unique();
        $weatherMap = WeatherData::whereIn('weather_date', $dates)
            ->get()
            ->keyBy('weather_date');

        return response()->json(
            $forecasts->map(fn ($f) => [
                'id' => $f->id,
                'forecast_date' => $f->forecast_date,
                'predicted_revenue' => $f->predicted_revenue,
                'season' => $f->season,
                'model_version' => $f->model_version,
                'weather' => $weatherMap[$f->forecast_date->toDateString()] ?? null,
            ])
        );
    }

    public function chart()
    {
        return response()->json(
            RevenueForecast::orderBy('forecast_date')
                ->get(['forecast_date', 'predicted_revenue', 'season'])
        );
    }

    public function show(RevenueForecast $forecast)
    {
        $weather = WeatherData::where('weather_date', $forecast->forecast_date->toDateString())->first();

        return response()->json([
            'id' => $forecast->id,
            'forecast_date' => $forecast->forecast_date,
            'predicted_revenue' => $forecast->predicted_revenue,
            'season' => $forecast->season,
            'model_version' => $forecast->model_version,
            'weather' => $weather,
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'forecast_date' => 'required|date',
            'predicted_revenue' => 'required|numeric|min:0',
            'season' => 'nullable|string',
            'model_version' => 'nullable|string',
        ]);

        if (empty($data['season'])) {
            $month = (int) date('n', strtotime($data['forecast_date']));
            $data['season'] = $month >= 1 && $month <= 6 ? 'Peak' : 'Off-Peak';
        }

        $forecast = RevenueForecast::create($data);
        $weather = WeatherData::where('weather_date', $forecast->forecast_date->toDateString())->first();

        return response()->json([
            'id' => $forecast->id,
            'forecast_date' => $forecast->forecast_date,
            'predicted_revenue' => $forecast->predicted_revenue,
            'season' => $forecast->season,
            'model_version' => $forecast->model_version,
            'weather' => $weather,
        ], 201);
    }
}
