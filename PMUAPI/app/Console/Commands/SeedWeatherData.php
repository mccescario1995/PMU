<?php

namespace App\Console\Commands;

use App\Models\WeatherData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SeedWeatherData extends Command
{
    protected $signature = 'weather:seed {--start=2020-01-01 : Start date (Y-m-d)} {--end=2025-12-31 : End date (Y-m-d)} {--force : Insert even if dates exist}';

    protected $description = 'Download historical weather from Open-Meteo and seed weather_data table';

    public function handle(): int
    {
        $start = $this->option('start');
        $end = $this->option('end');
        $force = $this->option('force');

        $url = 'https://archive-api.open-meteo.com/v1/archive'
            . '?latitude=13.5049'
            . '&longitude=123.0434'
            . "&start_date={$start}"
            . "&end_date={$end}"
            . '&daily=temperature_2m_mean,precipitation_sum,wind_speed_10m_max'
            . '&temperature_unit=celsius'
            . '&wind_speed_unit=kmh'
            . '&precipitation_unit=mm'
            . '&timezone=Asia/Manila';

        $this->info("Downloading weather data: {$start} to {$end}");

        $response = Http::timeout(120)->withOptions(['verify' => false])->get($url);

        if (! $response->successful()) {
            $this->error("Open-Meteo error: {$response->status()} — {$response->json('reason', 'Unknown')}");

            return 1;
        }

        $data = $response->json('daily');

        if (empty($data['time'])) {
            $this->error('No daily data returned.');

            return 1;
        }

        $dates = $data['time'];
        $temps = $data['temperature_2m_mean'] ?? [];
        $rains = $data['precipitation_sum'] ?? [];
        $winds = $data['wind_speed_10m_max'] ?? [];

        $total = count($dates);
        $this->info("Received {$total} daily records.");

        if (! $force) {
            $existing = WeatherData::whereBetween('weather_date', [$start, $end])
                ->pluck('weather_date')
                ->map(fn ($d) => (string) $d)
                ->toArray();
            $existing = array_flip($existing);
        } else {
            $existing = [];
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $batch = [];
        $batchSize = 500;
        $inserted = 0;
        $skipped = 0;

        foreach ($dates as $i => $date) {
            $dateStr = (string) $date;

            if (! $force && isset($existing[$dateStr])) {
                $skipped++;
                $bar->advance();

                continue;
            }

            $batch[] = [
                'weather_date' => $dateStr,
                'temperature' => isset($temps[$i]) ? round((float) $temps[$i], 2) : null,
                'rainfall_mm' => isset($rains[$i]) ? round((float) $rains[$i], 2) : null,
                'wind_speed' => isset($winds[$i]) ? round((float) $winds[$i], 2) : null,
                'source' => 'openmeteo_historical',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                WeatherData::insertOrIgnore($batch);
                $inserted += count($batch);
                $batch = [];
            }

            $bar->advance();
        }

        if (! empty($batch)) {
            WeatherData::insertOrIgnore($batch);
            $inserted += count($batch);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Seeded: {$inserted} new rows, {$skipped} skipped.");

        return 0;
    }
}
