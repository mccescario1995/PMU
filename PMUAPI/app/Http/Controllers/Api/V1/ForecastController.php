<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RevenueForecast;
use App\Models\WeatherData;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    use LogsAudit;

    public function index(WeatherService $weather)
    {
        $query = RevenueForecast::orderBy('forecast_date');

        if (request()->has('page')) {
            $forecasts = $query->paginate(request('per_page', 10));
        } else {
            $forecasts = $query->get();
        }

        $dates = $forecasts->pluck('forecast_date')->map(fn ($d) => $d->toDateString())->unique();
        $weather->ensureWeatherForDates($dates->toArray());

        $weatherMap = WeatherData::whereIn('weather_date', $dates)
            ->get()
            ->keyBy('weather_date');

        if (request()->has('page')) {
            $forecasts->getCollection()->transform(fn ($f) => $this->toRow($f, $weatherMap));
            return response()->json($forecasts);
        }

        return response()->json(
            $forecasts->map(fn ($f) => $this->toRow($f, $weatherMap))
        );
    }

    public function table(WeatherService $weather)
    {
        $query = RevenueForecast::orderBy('forecast_date');

        if (request()->has('page')) {
            $forecasts = $query->paginate(request('per_page', 10));
        } else {
            $forecasts = $query->get();
        }

        $dates = $forecasts->pluck('forecast_date')->map(fn ($d) => $d->toDateString())->unique();
        $weather->ensureWeatherForDates($dates->toArray());

        $weatherMap = WeatherData::whereIn('weather_date', $dates)
            ->get()
            ->keyBy('weather_date');

        if (request()->has('page')) {
            $forecasts->getCollection()->transform(fn ($f) => [
                'period' => $f->forecast_date->format('M Y'),
                'forecast_date' => $f->forecast_date,
                'projected_revenue' => $f->predicted_revenue,
                'season' => $f->season,
                'weather' => $weatherMap[$f->forecast_date->toDateString()] ?? null,
            ]);
            return response()->json($forecasts);
        }

        return response()->json(
            $forecasts->map(fn ($f) => [
                'period' => $f->forecast_date->format('M Y'),
                'forecast_date' => $f->forecast_date,
                'projected_revenue' => $f->predicted_revenue,
                'season' => $f->season,
                'weather' => $weatherMap[$f->forecast_date->toDateString()] ?? null,
            ])
        );
    }

    protected function toRow(RevenueForecast $f, \Illuminate\Support\Collection $weatherMap): array
    {
        return [
            'id' => $f->id,
            'period' => $f->forecast_date->format('M Y'),
            'forecast_date' => $f->forecast_date,
            'predicted_revenue' => $f->predicted_revenue,
            'season' => $f->season,
            'model_version' => $f->model_version,
            'weather' => $weatherMap[$f->forecast_date->toDateString()] ?? null,
        ];
    }

    public function chart()
    {
        return response()->json(
            RevenueForecast::orderBy('forecast_date')
                ->get(['forecast_date', 'predicted_revenue', 'season'])
        );
    }

    public function show(RevenueForecast $forecast, WeatherService $weather)
    {
        $weather->ensureWeatherForDates([$forecast->forecast_date->toDateString()]);
        $weatherData = WeatherData::where('weather_date', $forecast->forecast_date->toDateString())->first();

        return response()->json([
            'id' => $forecast->id,
            'forecast_date' => $forecast->forecast_date,
            'predicted_revenue' => $forecast->predicted_revenue,
            'season' => $forecast->season,
            'model_version' => $forecast->model_version,
            'weather' => $weatherData,
        ]);
    }

    public function update(Request $request, RevenueForecast $forecast)
    {
        $data = $request->validate([
            'forecast_date' => 'sometimes|required|date',
            'predicted_revenue' => 'sometimes|required|numeric|min:0',
            'season' => 'nullable|string',
            'model_version' => 'nullable|string',
        ]);

        $oldValues = $this->modelToArray($forecast, ['forecast_date', 'predicted_revenue', 'season', 'model_version']);

        $forecast->update($data);

        $this->logAudit('update', 'revenue_forecasts', $forecast->id, $oldValues, $this->modelToArray($forecast, ['forecast_date', 'predicted_revenue', 'season', 'model_version']));

        return response()->json($forecast);
    }

    public function destroy(RevenueForecast $forecast)
    {
        $this->logAudit('delete', 'revenue_forecasts', $forecast->id, $this->modelToArray($forecast, ['forecast_date', 'predicted_revenue', 'season', 'model_version']), null);

        $forecast->delete();

        return response()->noContent();
    }

    public function generate(Request $request, WeatherService $weather)
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
        $this->logAudit('create', 'revenue_forecasts', $forecast->id, null, $this->modelToArray($forecast, ['forecast_date', 'predicted_revenue', 'season', 'model_version']));
        $weather->ensureWeatherForDates([$forecast->forecast_date->toDateString()]);
        $weatherData = WeatherData::where('weather_date', $forecast->forecast_date->toDateString())->first();

        return response()->json([
            'id' => $forecast->id,
            'forecast_date' => $forecast->forecast_date,
            'predicted_revenue' => $forecast->predicted_revenue,
            'season' => $forecast->season,
            'model_version' => $forecast->model_version,
            'weather' => $weatherData,
        ], 201);
    }

    public function runModel(Request $request, WeatherService $weather)
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

        $url = $pmumlUrl.'/forecast';
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

        if ($httpCode !== 200 || ! $response) {
            return response()->json(['error' => 'PMUML request failed', 'details' => $response], 502);
        }

        $result = json_decode($response, true);
        if (! isset($result['forecasts']) || ! is_array($result['forecasts'])) {
            return response()->json(['error' => 'Invalid PMUML response', 'details' => $result], 502);
        }

        $saved = [];
        foreach ($result['forecasts'] as $item) {
            $forecastDate = $item['date'] ?? null;
            $predicted = $item['predicted_revenue'] ?? null;

            if (! $forecastDate || $predicted === null) {
                continue;
            }

            $month = (int) date('n', strtotime($forecastDate));
            $season = $month >= 1 && $month <= 6 ? 'Peak' : 'Off-Peak';

            $forecast = RevenueForecast::create([
                'forecast_date' => $forecastDate,
                'predicted_revenue' => $predicted,
                'season' => $season,
                'model_version' => $model.'-v1',
            ]);

            $saved[] = [
                'id' => $forecast->id,
                'forecast_date' => $forecast->forecast_date,
                'predicted_revenue' => $forecast->predicted_revenue,
                'season' => $forecast->season,
                'model_version' => $forecast->model_version,
            ];
        }

        $weather->ensureWeatherForDates(
            collect($saved)->pluck('forecast_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->toArray()
        );

        return response()->json([
            'model' => $model,
            'metrics' => $result['metrics'] ?? [],
            'saved_forecasts' => $saved,
        ], 201);
    }
}
