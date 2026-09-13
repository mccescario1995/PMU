<?php

namespace App\Console\Commands;

use App\Models\RevenueForecast;
use App\Models\RevenueHistory;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedForecasts extends Command
{
    protected $signature = 'forecast:seed {--days=30 : Forecast days}';

    protected $description = 'Generate revenue forecasts from historical data';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $history = RevenueHistory::orderByDesc('revenue_date')
            ->take(30)
            ->get(['revenue_date', 'total_revenue'])
            ->sortBy('revenue_date');

        if ($history->isEmpty()) {
            $this->error('No revenue history found.');

            return 1;
        }

        $avg = (float) $history->avg('total_revenue');
        $trend = $this->calculateTrend($history);

        $lastDate = Carbon::parse($history->last()->revenue_date);
        $weather = new WeatherService();

        $this->info("Generating {$days}-day forecast (avg: {$avg}, trend: {$trend})");

        $batch = [];
        for ($i = 1; $i <= $days; $i++) {
            $date = $lastDate->copy()->addDays($i);
            $dateStr = $date->toDateString();

            $seasonal = $this->getSeasonalFactor($date->month);
            $predicted = max(0, round($avg * $seasonal * (1 + $trend * $i / 30), 2));

            $month = (int) $date->month;
            $season = ($month >= 1 && $month <= 6) ? 'Peak' : 'Off-Peak';

            $batch[] = [
                'forecast_date' => $dateStr,
                'predicted_revenue' => $predicted,
                'season' => $season,
                'model_version' => 'linear-regression-v1',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        RevenueForecast::insertOrIgnore($batch);

        $this->info('Inserted ' . count($batch) . ' forecasts');

        try {
            $weather->ensureWeatherForDates(collect($batch)->pluck('forecast_date')->toArray());
            $this->info('Weather ensured for forecast dates.');
        } catch (\Exception $e) {
            $this->warn('Weather skip: ' . $e->getMessage());
        }

        return 0;
    }

    private function calculateTrend($history): float
    {
        $n = $history->count();
        if ($n < 2) {
            return 0;
        }

        $xMean = ($n - 1) / 2;
        $yMean = (float) $history->avg('total_revenue');

        $num = 0;
        $den = 0;
        foreach ($history as $i => $row) {
            $x = (float) $i;
            $y = (float) $row->total_revenue;
            $num += ($x - $xMean) * ($y - $yMean);
            $den += ($x - $xMean) ** 2;
        }

        return $den == 0 ? 0 : $num / $den;
    }

    private function getSeasonalFactor(int $month): float
    {
        $factors = [
            1 => 1.1, 2 => 0.9, 3 => 1.0, 4 => 0.95, 5 => 1.05, 6 => 1.2,
            7 => 1.1, 8 => 0.95, 9 => 1.0, 10 => 1.15, 11 => 1.3, 12 => 1.25,
        ];

        return $factors[$month] ?? 1.0;
    }
}
