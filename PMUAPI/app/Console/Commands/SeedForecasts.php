<?php

namespace App\Console\Commands;

use App\Models\RevenueForecast;
use App\Models\RevenueForecastAmira;
use App\Models\RevenueForecastLinearRegression;
use App\Models\RevenueForecastSamira;
use App\Models\RevenueHistory;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedForecasts extends Command
{
    protected $signature = 'forecast:seed {--days=30 : Forecast days} {--model=linear_regression : Target model (arima, sarima, linear_regression)}';

    protected $description = 'Generate revenue forecasts from historical data';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $model = $this->option('model');

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

        // Compute monthly seasonal factors from actual historical data
        $monthlyFactors = $this->computeMonthlyFactors();

        // Compute data-driven peak/off-peak classification
        $peakMonths = $this->computePeakMonths();

        $lastDate = Carbon::parse($history->last()->revenue_date);
        $weather = new WeatherService();

        $this->info("Generating {$days}-day forecast (avg: {$avg}, trend: {$trend})");

        $batch = [];
        for ($i = 1; $i <= $days; $i++) {
            $date = $lastDate->copy()->addDays($i);
            $dateStr = $date->toDateString();

            $seasonal = $monthlyFactors[$date->month] ?? 1.0;
            $predicted = max(0, round($avg * $seasonal * (1 + $trend * $i / 30), 2));

            $season = in_array((int) $date->month, $peakMonths) ? 'Peak' : 'Off-Peak';

            $batch[] = [
                'forecast_date' => $dateStr,
                'predicted_revenue' => $predicted,
                'season' => $season,
                'model_version' => $model.'-v1',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Save to model-specific table
        $targetClass = $this->getModelClass($model);
        $targetClass::insertOrIgnore($batch);

        $this->info('Inserted ' . count($batch) . ' forecasts into ' . $targetClass::getTable());

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

    /**
     * Compute monthly seasonal factors from actual revenue history.
     * Factor = monthly average / annual average.
     * A factor > 1.0 means that month is above average; < 1.0 means below.
     */
    private function computeMonthlyFactors(): array
    {
        $monthlyAvg = RevenueHistory::selectRaw('MONTH(revenue_date) as month, AVG(total_revenue) as avg_rev')
            ->groupBy('month')
            ->pluck('avg_rev', 'month')
            ->toArray();

        if (empty($monthlyAvg)) {
            return [];
        }

        $annualAvg = array_sum($monthlyAvg) / count($monthlyAvg);

        if ($annualAvg == 0) {
            return [];
        }

        $factors = [];
        for ($m = 1; $m <= 12; $m++) {
            $factors[$m] = isset($monthlyAvg[$m]) ? $monthlyAvg[$m] / $annualAvg : 1.0;
        }

        return $factors;
    }

    /**
     * Compute data-driven peak months.
     * A month is "Peak" if its average revenue is above the annual average.
     */
    private function computePeakMonths(): array
    {
        $monthlyAvg = RevenueHistory::selectRaw('MONTH(revenue_date) as month, AVG(total_revenue) as avg_rev')
            ->groupBy('month')
            ->pluck('avg_rev', 'month')
            ->toArray();

        if (empty($monthlyAvg)) {
            return [];
        }

        $annualAvg = array_sum($monthlyAvg) / count($monthlyAvg);

        $peak = [];
        for ($m = 1; $m <= 12; $m++) {
            if (isset($monthlyAvg[$m]) && $monthlyAvg[$m] > $annualAvg) {
                $peak[] = $m;
            }
        }

        return $peak;
    }

    /**
     * Map model name to its dedicated forecast model class.
     */
    private function getModelClass(string $model): string
    {
        return match ($model) {
            'amira', 'arima' => RevenueForecastAmira::class,
            'samira', 'sarima' => RevenueForecastSamira::class,
            'linear_regression' => RevenueForecastLinearRegression::class,
            default => RevenueForecast::class,
        };
    }
}