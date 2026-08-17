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

    public function runModel(Request $request)
    {
        $data = $request->validate([
            'model' => 'required|string|in:linear_regression,amira,samira',
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $model = $data['model'];
        $days = $data['days'] ?? 30;

        $pmumlUrl = rtrim(env('PMUML_URL', ''), '/');
        if (empty($pmumlUrl)) {
            return response()->json(['error' => 'PMUML_URL not configured'], 500);
        }

        $url = $pmumlUrl . '/forecast';
        $payload = json_encode(['model' => $model, 'days' => $days]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return response()->json(['error' => 'PMUML request failed', 'details' => $response], 502);
        }

        $result = json_decode($response, true);
        if (!isset($result['forecasts']) || !is_array($result['forecasts'])) {
            return response()->json(['error' => 'Invalid PMUML response', 'details' => $result], 502);
        }

        $saved = [];
        foreach ($result['forecasts'] as $item) {
            $forecastDate = $item['date'] ?? null;
            $predicted = $item['predicted_revenue'] ?? null;

            if (!$forecastDate || $predicted === null) {
                continue;
            }

            $month = (int) date('n', strtotime($forecastDate));
            $season = $month >= 1 && $month <= 6 ? 'Peak' : 'Off-Peak';

            $forecast = RevenueForecast::create([
                'forecast_date' => $forecastDate,
                'predicted_revenue' => $predicted,
                'season' => $season,
                'model_version' => $model . '-v1',
            ]);

            $saved[] = [
                'id' => $forecast->id,
                'forecast_date' => $forecast->forecast_date,
                'predicted_revenue' => $forecast->predicted_revenue,
                'season' => $forecast->season,
                'model_version' => $forecast->model_version,
            ];
        }

        return response()->json([
            'model' => $model,
            'metrics' => $result['metrics'] ?? [],
            'saved_forecasts' => $saved,
        ], 201);
    }
}
