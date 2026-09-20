<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\TrainForecastModel;
use App\Models\RevenueForecastAmira;
use App\Models\RevenueForecastLinearRegression;
use App\Models\RevenueForecastSamira;
use App\Models\WeatherData;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        // Determine model class from model_version
        $modelVersion = $data['model_version'] ?? '';
        $modelClass = $this->getModelClassFromVersion($modelVersion);

        // Upsert by forecast_date (unique constraint)
        $forecast = $modelClass::updateOrCreate(
            ['forecast_date' => $data['forecast_date']],
            $data
        );
        
        $table = $modelClass::getTable();
        $this->logAudit('create', $table, $forecast->id, null, $this->modelToArray($forecast, ['forecast_date', 'predicted_revenue', 'season', 'model_version']));
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

    protected function getModelClassFromVersion(string $modelVersion): string
    {
        if (str_contains($modelVersion, 'arima') || str_contains($modelVersion, 'amira')) {
            return RevenueForecastAmira::class;
        }
        if (str_contains($modelVersion, 'sarima') || str_contains($modelVersion, 'samira')) {
            return RevenueForecastSamira::class;
        }
        if (str_contains($modelVersion, 'linear')) {
            return RevenueForecastLinearRegression::class;
        }
        return RevenueForecastSamira::class;
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
            'sync' => 'nullable|boolean',
        ]);

        if (! $model) {
            return response()->json(['error' => 'Model is required'], 400);
        }

        $days = $data['days'] ?? 30;
        $sync = $data['sync'] ?? false;

        // If sync mode, run directly and return results
        if ($sync) {
            return $this->runModelSync($model, $days, $weather);
        }

        $pmumlUrl = rtrim(env('PMUML_URL', ''), '/');
        if (empty($pmumlUrl)) {
            Log::error('PMUML_URL not configured');
            return response()->json(['error' => 'PMUML_URL not configured'], 500);
        }

        // Generate task ID for tracking
        $taskId = 'train_' . $model . '_' . Str::random(12);

        // Initialize status in cache
        Cache::put("forecast_train_{$taskId}", [
            'status' => 'pending',
            'model' => $model,
            'days' => $days,
            'progress' => 0,
            'message' => 'Queued for training',
            'created_at' => now()->toISOString(),
        ], now()->addHours(24));

        // Dispatch async job
        TrainForecastModel::dispatch($model, $days, $taskId);

        return response()->json([
            'task_id' => $taskId,
            'model' => $model,
            'status' => 'pending',
            'message' => 'Training started. Poll /v1/forecasts/train/status/{task_id} for progress.',
        ], 202);
    }

    protected function runModelSync(string $model, int $days, WeatherService $weather)
    {
        $pmumlUrl = rtrim(env('PMUML_URL', ''), '/');
        if (empty($pmumlUrl)) {
            return response()->json(['error' => 'PMUML_URL not configured'], 500);
        }

        $pmuModel = match ($model) {
            'arima' => 'amira',
            'sarima' => 'samira',
            default => $model,
        };

        $forecastClass = $this->getModelClass($model);
        if ($forecastClass::count() > 0) {
            $forecastClass::truncate();
        }

        $url = $pmumlUrl . '/forecast?model=' . urlencode($pmuModel) . '&days=' . urlencode($days) . '&post_to_api=false';

        Log::info('PMUML sync request starting', [
            'url' => $url,
            'model' => $model,
            'days' => $days,
        ]);

        $timeout = $model === 'sarima' ? 600 : 120;

        try {
            $response = Http::timeout($timeout)
                ->post($url, []);

            if (!$response->successful()) {
                return response()->json(['error' => 'PMUML request failed', 'status' => $response->status()], 502);
            }

            $result = $response->json();

            if (!isset($result['forecasts']) || !is_array($result['forecasts'])) {
                return response()->json(['error' => 'Invalid PMUML response', 'details' => $result], 502);
            }

            $peakMonths = RevenueForecastSamira::computePeakMonths();
            $saved = [];

            foreach ($result['forecasts'] as $item) {
                $forecastDate = $item['date'] ?? null;
                $predicted = $item['predicted_revenue'] ?? null;

                if (!$forecastDate || $predicted === null) {
                    continue;
                }

                $month = (int) date('n', strtotime($forecastDate));
                $season = in_array($month, $peakMonths) ? 'Peak' : 'Off-Peak';

                $forecast = $forecastClass::create([
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

            $weather->ensureWeatherForDates(
                collect($saved)->pluck('forecast_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->toArray()
            );

            return response()->json([
                'model' => $model,
                'metrics' => $result['metrics'] ?? [],
                'saved_forecasts' => $saved,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('PMUML sync training error', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Training failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function trainStatus(Request $request, string $taskId)
    {
        $status = Cache::get("forecast_train_{$taskId}");

        if (! $status) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        // If completed, also return the forecasts
        if ($status['status'] === 'completed') {
            $model = $status['model'];
            $forecastClass = $this->getModelClass($model);
            $forecasts = $forecastClass::orderBy('forecast_date')->get([
                'id', 'forecast_date', 'predicted_revenue', 'season', 'model_version'
            ])->map(fn ($f) => [
                'id' => $f->id,
                'forecast_date' => $f->forecast_date,
                'predicted_revenue' => $f->predicted_revenue,
                'season' => $f->season,
                'model_version' => $f->model_version,
            ]);

            $status['forecasts'] = $forecasts;
            $status['count'] = $forecasts->count();
        }

        return response()->json($status);
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
