<?php

namespace App\Jobs;

use App\Models\RevenueForecastAmira;
use App\Models\RevenueForecastLinearRegression;
use App\Models\RevenueForecastSamira;
use App\Models\WeatherData;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TrainForecastModel implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $model,
        public int $days,
        public string $taskId
    ) {}

    public function handle(WeatherService $weather): void
    {
        $this->updateStatus('running', 10, 'Fetching historical data...');

        $pmumlUrl = rtrim(env('PMUML_URL', ''), '/');
        if (empty($pmumlUrl)) {
            $this->updateStatus('failed', 0, 'PMUML_URL not configured');
            Log::error('PMUML_URL not configured', ['task_id' => $this->taskId]);
            return;
        }

        $pmuModel = match ($this->model) {
            'arima' => 'amira',
            'sarima' => 'samira',
            default => $this->model,
        };

        $forecastClass = $this->getModelClass($this->model);
        if ($forecastClass::count() > 0) {
            $forecastClass::truncate();
        }

        $url = $pmumlUrl . '/forecast?model=' . urlencode($pmuModel) . '&days=' . urlencode($this->days) . '&post_to_api=false';

        $this->updateStatus('running', 30, 'Training model...');

        Log::info('PMUML async request starting', [
            'task_id' => $this->taskId,
            'url' => $url,
            'model' => $this->model,
            'days' => $this->days,
        ]);

        $timeout = $this->model === 'sarima' ? 600 : 120;

        try {
            $response = Http::timeout($timeout)
                ->post($url, []);

            $this->updateStatus('running', 70, 'Generating forecasts...');

            if (!$response->successful()) {
                $this->updateStatus('failed', 0, 'PMUML request failed: ' . $response->status());
                Log::error('PMUML request failed', [
                    'task_id' => $this->taskId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return;
            }

            $result = $response->json();

            if (!isset($result['forecasts']) || !is_array($result['forecasts'])) {
                $this->updateStatus('failed', 0, 'Invalid PMUML response format');
                Log::error('Invalid PMUML response format', [
                    'task_id' => $this->taskId,
                    'response' => $result,
                ]);
                return;
            }

            $this->updateStatus('running', 90, 'Saving forecasts...');

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
                    'model_version' => $this->model . '-v1',
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

            $this->updateStatus('completed', 100, 'Training completed', [
                'saved_count' => count($saved),
                'metrics' => $result['metrics'] ?? [],
            ]);

            Log::info('PMUML async training completed', [
                'task_id' => $this->taskId,
                'model' => $this->model,
                'saved_count' => count($saved),
                'metrics' => $result['metrics'] ?? [],
            ]);

        } catch (\Throwable $e) {
            $this->updateStatus('failed', 0, 'Training error: ' . $e->getMessage());
            Log::error('PMUML async training error', [
                'task_id' => $this->taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function updateStatus(string $status, int $progress, string $message, array $extra = []): void
    {
        $key = "forecast_train_{$this->taskId}";
        $data = Cache::get($key, []);
        $data = array_merge($data, [
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'updated_at' => now()->toISOString(),
        ], $extra);
        Cache::put($key, $data, now()->addHours(24));
    }

    protected function getModelClass(string $model): string
    {
        return match ($model) {
            'amira', 'arima' => RevenueForecastAmira::class,
            'samira', 'sarima' => RevenueForecastSamira::class,
            'linear_regression' => RevenueForecastLinearRegression::class,
            default => RevenueForecastSamira::class,
        };
    }
}