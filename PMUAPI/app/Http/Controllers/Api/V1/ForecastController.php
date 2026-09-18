<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RevenueForecastAmira;
use App\Models\RevenueForecastLinearRegression;
use App\Models\RevenueForecastSamira;
use App\Models\WeatherData;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ForecastController extends Controller
{
    use LogsAudit;

    public function index(WeatherService $weather)
    {
        $query = RevenueForecastSamira::orderBy('forecast_date');

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
        $query = RevenueForecastSamira::orderBy('forecast_date');

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

    protected function toRow($f, \Illuminate\Support\Collection $weatherMap): array
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
            RevenueForecastSamira::orderBy('forecast_date')
                ->get(['forecast_date', 'predicted_revenue', 'season'])
        );
    }

    public function show(Request $request, $forecast, WeatherService $weather)
    {
        $model = $request->query('model');
        if ($model) {
            $class = $this->getModelClass($model);
            $record = $class::find($forecast);
            if (! $record) {
                return response()->json(['error' => 'Forecast not found'], 404);
            }
            $weather->ensureWeatherForDates([$record->forecast_date->toDateString()]);
            $weatherData = WeatherData::where('weather_date', $record->forecast_date->toDateString())->first();
            return response()->json([
                'id' => $record->id,
                'forecast_date' => $record->forecast_date,
                'predicted_revenue' => $record->predicted_revenue,
                'season' => $record->season,
                'model_version' => $record->model_version,
                'weather' => $weatherData,
            ]);
        }

        // Default: look up in revenue_forecasts_samira table
        $record = RevenueForecastSamira::find($forecast);
        if (! $record) {
            return response()->json(['error' => 'Forecast not found'], 404);
        }
        $weather->ensureWeatherForDates([$record->forecast_date->toDateString()]);
        $weatherData = WeatherData::where('weather_date', $record->forecast_date->toDateString())->first();

        return response()->json([
            'id' => $record->id,
            'forecast_date' => $record->forecast_date,
            'predicted_revenue' => $record->predicted_revenue,
            'season' => $record->season,
            'model_version' => $record->model_version,
            'weather' => $weatherData,
        ]);
    }

    public function update(Request $request, $forecast)
    {
        $model = $request->query('model');
        $data = $request->validate([
            'forecast_date' => 'sometimes|required|date',
            'predicted_revenue' => 'sometimes|required|numeric|min:0',
            'season' => 'nullable|string',
            'model_version' => 'nullable|string',
        ]);

        if ($model) {
            $class = $this->getModelClass($model);
            $record = $class::find($forecast);
            if (! $record) {
                return response()->json(['error' => 'Forecast not found'], 404);
            }
            $oldValues = $this->modelToArray($record, ['forecast_date', 'predicted_revenue', 'season', 'model_version']);
            $record->update($data);
            $this->logAudit('update', $class::getTable(), $record->id, $oldValues, $this->modelToArray($record, ['forecast_date', 'predicted_revenue', 'season', 'model_version']));
            return response()->json($record);
        }

        // Default: look up in revenue_forecasts_samira table
        $record = RevenueForecastSamira::find($forecast);
        if (! $record) {
            return response()->json(['error' => 'Forecast not found'], 404);
        }
        $oldValues = $this->modelToArray($record, ['forecast_date', 'predicted_revenue', 'season', 'model_version']);
        $record->update($data);
        $this->logAudit('update', 'revenue_forecasts_samira', $record->id, $oldValues, $this->modelToArray($record, ['forecast_date', 'predicted_revenue', 'season', 'model_version']));
        return response()->json($record);
    }

    public function destroy(Request $request, $forecast)
    {
        $model = $request->query('model');

        if ($model) {
            $class = $this->getModelClass($model);
            $record = $class::find($forecast);
            if (! $record) {
                return response()->json(['error' => 'Forecast not found'], 404);
            }
            $this->logAudit('delete', $class::getTable(), $record->id, $this->modelToArray($record, ['forecast_date', 'predicted_revenue', 'season', 'model_version']), null);
            $record->delete();
            return response()->noContent();
        }

        // Default: look up in revenue_forecasts_samira table
        $record = RevenueForecastSamira::find($forecast);
        if (! $record) {
            return response()->json(['error' => 'Forecast not found'], 404);
        }
        $this->logAudit('delete', 'revenue_forecasts_samira', $record->id, $this->modelToArray($record, ['forecast_date', 'predicted_revenue', 'season', 'model_version']), null);
        $record->delete();
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
            $peakMonths = RevenueForecastSamira::computePeakMonths();
            $data['season'] = in_array($month, $peakMonths) ? 'Peak' : 'Off-Peak';
        }

        $forecast = RevenueForecastSamira::create($data);
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

    public function runModel(Request $request, WeatherService $weather, ?string $model = null)
    {
        // Use route default if not provided in body
        if (! $model) {
            $model = $request->input('model');
        }

        $data = $request->validate([
            'model' => 'nullable|string|in:linear_regression,arima,sarima',
            'days' => 'nullable|integer|min:1|max:366',
        ]);

        if (! $model) {
            return response()->json(['error' => 'Model is required'], 400);
        }

        $days = $data['days'] ?? 30;

        $pmumlUrl = rtrim(env('PMUML_URL', ''), '/');
        if (empty($pmumlUrl)) {
            Log::error('PMUML_URL not configured');
            return response()->json(['error' => 'PMUML_URL not configured'], 500);
        }

        // Map frontend model names to PMUML model names
        $pmuModel = match ($model) {
            'arima' => 'amira',
            'sarima' => 'samira',
            default => $model,
        };

        // // Clear existing forecasts for this model before retraining
        // $forecastClass = $this->getModelClass($model);
        // if ($forecastClass::count() > 0) {
        //     $forecastClass::truncate();
        // }

        $url = $pmumlUrl.'/forecast?model='.urlencode($pmuModel).'&days='.urlencode($days).'&post_to_api=false';
        $payload = json_encode([]);

        Log::info('PMUML request starting', [
            'url' => $url,
            'payload' => $payload,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => $model === 'sarima' ? 300 : 120,
        ]);

        // LOCAL RENDER
        if (app()->environment('local')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        Log::info('PMUML response received', [
            'url' => $url,
            'http_code' => $httpCode,
            'response' => $response,
            'curl_error' => $curlError,
        ]);

        if ($httpCode !== 200 || ! $response) {
            return response()->json(['error' => 'PMUML request failed', 'details' => $response], 502);
        }

        $result = json_decode($response, true);
        if (! isset($result['forecasts']) || ! is_array($result['forecasts'])) {
            Log::error('Invalid PMUML response format', ['response' => $result]);
            return response()->json(['error' => 'Invalid PMUML response', 'details' => $result], 502);
        }

        $peakMonths = RevenueForecastSamira::computePeakMonths();

        $saved = [];
        foreach ($result['forecasts'] as $item) {
            $forecastDate = $item['date'] ?? null;
            $predicted = $item['predicted_revenue'] ?? null;

            if (! $forecastDate || $predicted === null) {
                continue;
            }

            $month = (int) date('n', strtotime($forecastDate));
            $season = in_array($month, $peakMonths) ? 'Peak' : 'Off-Peak';

            // Save to model-specific table
            $forecastClass = $this->getModelClass($model);
            $forecast = $forecastClass::create([
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

    /**
     * Map model name to its dedicated forecast model class.
     */
    protected function getModelClass(string $model): string
    {
        return match ($model) {
            'amira', 'arima' => RevenueForecastAmira::class,
            'samira', 'sarima' => RevenueForecastSamira::class,
            'linear_regression' => RevenueForecastLinearRegression::class,
            default => RevenueForecastSamira::class,
        };
    }

    /**
     * List forecasts for a specific model.
     */
    public function byModel(string $model, WeatherService $weather)
    {
        $class = $this->getModelClass($model);
        $query = $class::orderBy('forecast_date');

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
}
