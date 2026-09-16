<?php

namespace App\Services;

use App\Models\WeatherData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected string $baseUrl = 'https://archive-api.open-meteo.com/v1/archive';

    protected string $forecastUrl = 'https://api.open-meteo.com/v1/forecast';

    protected float $lat = 13.5049;

    protected float $lon = 123.0434;

    public function fetchForecast(int $days = 7): Collection
    {
        $days = max(1, min($days, 16));

        $url = $this->forecastUrl
            . "?latitude={$this->lat}"
            . "&longitude={$this->lon}"
            . '&daily=temperature_2m_mean,precipitation_sum,wind_speed_10m_max'
            . '&temperature_unit=celsius'
            . '&wind_speed_unit=kmh'
            . '&precipitation_unit=mm'
            . '&timezone=Asia/Manila'
            . '&forecast_days=' . $days;

        try {
            $response = Http::timeout(30)->withOptions(['verify' => false])->get($url);

            if (! $response->successful()) {
                Log::error('Open-Meteo forecast failed', ['status' => $response->status()]);

                return collect();
            }

            $data = $response->json('daily');
            if (empty($data['time'])) {
                return collect();
            }

            $out = collect();
            foreach ($data['time'] as $i => $date) {
                $out->push(WeatherData::updateOrCreate(
                    ['weather_date' => $date],
                    [
                        'temperature' => isset($data['temperature_2m_mean'][$i]) ? round((float) $data['temperature_2m_mean'][$i], 2) : null,
                        'rainfall_mm' => isset($data['precipitation_sum'][$i]) ? round((float) $data['precipitation_sum'][$i], 2) : null,
                        'wind_speed' => isset($data['wind_speed_10m_max'][$i]) ? round((float) $data['wind_speed_10m_max'][$i], 2) : null,
                        'source' => 'openmeteo_forecast',
                    ]
                ));
            }

            return $out;
        } catch (\Exception $e) {
            Log::error('Open-Meteo forecast exception', ['message' => $e->getMessage()]);

            return collect();
        }
    }

    public function fetchForDate(string $date): ?WeatherData
    {
        $existing = WeatherData::where('weather_date', $date)->first();

        if ($existing) {
            return $existing;
        }

        $today = Carbon::now()->startOfDay();
        $target = Carbon::parse($date)->startOfDay();

        if ($target->lessThan($today)) {
            return null;
        }

        return $this->fetchForecast(16)->firstWhere('weather_date', $date);
    }

    public function ensureWeatherForDates(array $dates): void
    {
        $missing = collect($dates)
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->filter(fn ($d) => ! WeatherData::where('weather_date', $d)->exists());

        if ($missing->isEmpty()) {
            return;
        }

        $hasFuture = $missing->contains(fn ($d) => Carbon::parse($d)->startOfDay()->greaterThanOrEqualTo(Carbon::now()->startOfDay()));

        if ($hasFuture) {
            $this->fetchForecast(16);
        }
    }
}