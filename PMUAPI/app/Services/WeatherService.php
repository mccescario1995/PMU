<?php

namespace App\Services;

use App\Models\WeatherData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected string $apiKey;

    protected string $url;

    protected string $defaultLocation;

    public function __construct()
    {
        $this->apiKey = config('services.openweather.key');
        $this->url = config('services.openweather.url');
        $this->defaultLocation = config('services.openweather.default_location', 'Pasao,Camarines Sur,PH');
    }

    public function fetchForDate(string $date): ?WeatherData
    {
        $existing = WeatherData::where('weather_date', $date)->first();

        if ($existing) {
            return $existing;
        }

        if (! $this->apiKey) {
            Log::warning('Weather API key not configured');

            return null;
        }

        try {
            $response = Http::timeout(10)
                ->get($this->url, [
                    'q' => $this->defaultLocation,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                ]);

            if (! $response->successful()) {
                Log::error('Weather API failed', ['status' => $response->status()]);

                return null;
            }

            $data = $response->json();

            return WeatherData::create([
                'weather_date' => $date,
                'rainfall_mm' => $data['rain']['1h'] ?? $data['rain']['3h'] ?? null,
                'wind_speed' => $data['wind']['speed'] ?? null,
                'temperature' => $data['main']['temp'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Weather API exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
