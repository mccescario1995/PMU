<?php

namespace App\Services;

use App\Models\WeatherData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected string $apiKey;

    protected string $baseUrl;

    protected string $defaultLocation;

    protected ?array $coords = null;

    public function __construct()
    {
        $this->apiKey = config('services.openweather.key');
        $this->baseUrl = rtrim(config('services.openweather.url'), '/');
        $this->defaultLocation = config('services.openweather.default_location', 'Pasao,Camarines Sur,PH');
    }

    protected function coords(): ?array
    {
        if ($this->coords !== null) {
            return $this->coords;
        }

        if (! $this->apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://api.openweathermap.org/geo/1.0/direct', [
                'q' => $this->defaultLocation,
                'limit' => 1,
                'appid' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $results = $response->json();
                if (! empty($results[0]['lat']) && ! empty($results[0]['lon'])) {
                    $this->coords = [
                        'lat' => $results[0]['lat'],
                        'lon' => $results[0]['lon'],
                    ];

                    return $this->coords;
                }
            }

            Log::error('OpenWeather geocoding failed', ['status' => $response->status()]);
        } catch (\Exception $e) {
            Log::error('OpenWeather geocoding exception', ['message' => $e->getMessage()]);
        }

        return null;
    }

    public function fetchForecast(int $days = 7): Collection
    {
        $coords = $this->coords();

        if (! $coords) {
            Log::warning('Weather coordinates unavailable; cannot fetch forecast');

            return collect();
        }

        if (! $this->apiKey) {
            Log::warning('Weather API key not configured');

            return collect();
        }

        try {
            $response = Http::timeout(10)->get($this->baseUrl, [
                'lat' => $coords['lat'],
                'lon' => $coords['lon'],
                'exclude' => 'current,minutely,hourly,alerts',
                'units' => 'metric',
                'appid' => $this->apiKey,
            ]);

            if (! $response->successful()) {
                Log::error('OpenWeather One Call failed', ['status' => $response->status()]);

                return collect();
            }

            $data = $response->json();
            $out = collect();

            foreach (array_slice($data['daily'] ?? [], 0, $days) as $day) {
                if (empty($day['dt'])) {
                    continue;
                }

                $date = Carbon::createFromTimestamp($day['dt'])->toDateString();

                $out->push(WeatherData::updateOrCreate(
                    ['weather_date' => $date],
                    [
                        'rainfall_mm' => $day['rain'] ?? null,
                        'temperature' => $day['temp']['day'] ?? null,
                        'wind_speed' => $day['wind_speed'] ?? null,
                        'source' => 'onecall_forecast',
                    ]
                ));
            }

            return $out;
        } catch (\Exception $e) {
            Log::error('OpenWeather One Call exception', ['message' => $e->getMessage()]);

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
            Log::info('Historical weather for past dates is not available on the free One Call tier; import it instead.', [
                'date' => $date,
            ]);

            return null;
        }

        return $this->fetchForecast(8)->firstWhere('weather_date', $date);
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
            $this->fetchForecast(8);
        }
    }
}
