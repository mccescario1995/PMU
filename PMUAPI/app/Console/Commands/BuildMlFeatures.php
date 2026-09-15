<?php

namespace App\Console\Commands;

use App\Models\TransactionRevenue;
use App\Models\TransactionRevenueFeature;
use App\Models\WeatherData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BuildMlFeatures extends Command
{
    protected $signature = 'ml:build-features {--days= : Limit to last N days of history}';

    protected $description = 'Build transaction_revenue and transaction_revenue_features tables from revenue_histories + weather_data';

    public function handle(): int
    {
        $this->info('Building transaction_revenue...');

        // Step 1: Upsert into transaction_revenue from revenue_histories + weather_data
        $query = DB::table('revenue_histories as rh')
            ->leftjoin('weather_data as wd', 'wd.weather_date', '=', 'rh.revenue_date')
            ->select([
                'rh.revenue_date',
                'rh.total_revenue as revenue_target',
                DB::raw('LOG(GREATEST(rh.total_revenue, 0.0001)) as log_revenue'),
                'wd.temperature as temp_celsius',
                'wd.rainfall_mm as precipitation_mm',
                'wd.wind_speed as wind_speed',
                DB::raw('YEAR(rh.revenue_date) as year_num'),
                DB::raw('MONTH(rh.revenue_date) as month_num'),
                DB::raw('DAY(rh.revenue_date) as day_num'),
                DB::raw('DAYOFWEEK(rh.revenue_date) as day_of_week'),
                DB::raw('QUARTER(rh.revenue_date) as quarter_num'),
                DB::raw('DAYOFWEEK(rh.revenue_date) IN (1, 7) as is_weekend'),
                DB::raw('DAY(rh.revenue_date) = 1 as is_month_start'),
                DB::raw('DAY(rh.revenue_date) = DAY(LAST_DAY(rh.revenue_date)) as is_month_end'),
            ])
            ->orderBy('rh.revenue_date')
            ->groupBy('rh.revenue_date');

        // Optional: limit to last N days
        if ($this->option('days')) {
            $days = (int) $this->option('days');
            $query->where('rh.revenue_date', '>=', now()->subDays($days)->toDateString());
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->error('No revenue history found.');

            return 1;
        }

        $this->info("Found {$rows->count()} revenue history record(s).");

        // Insert in batches
        $batch = [];
        foreach ($rows as $row) {
            $batch[] = (array) $row + ['created_at' => now(), 'updated_at' => now()];
        }

        $chunks = array_chunk($batch, 500);
        foreach ($chunks as $chunk) {
            TransactionRevenue::upsert($chunk, ['report_date'], [
                'revenue_target', 'log_revenue', 'temp_celsius',
                'precipitation_mm', 'wind_speed', 'year_num', 'month_num',
                'day_num', 'day_of_week', 'quarter_num', 'is_weekend',
                'is_month_start', 'is_month_end', 'updated_at',
            ]);
        }

        $this->info("Upserted {$rows->count()} row(s) into transaction_revenue.");

        // Step 2: Build transaction_revenue_features from transaction_revenue
        $this->info('Building transaction_revenue_features...');

        $featureRows = DB::table('transaction_revenue as tr')
            ->select([
                'tr.report_date',
                DB::raw('(SELECT tr2.revenue_target FROM transaction_revenue tr2 WHERE tr2.report_date = DATE_SUB(tr.report_date, INTERVAL 1 DAY)) as revenue_lag_1d'),
                DB::raw('(SELECT tr2.revenue_target FROM transaction_revenue tr2 WHERE tr2.report_date = DATE_SUB(tr.report_date, INTERVAL 7 DAY)) as revenue_lag_7d'),
                DB::raw('(SELECT tr2.revenue_target FROM transaction_revenue tr2 WHERE tr2.report_date = DATE_SUB(tr.report_date, INTERVAL 365 DAY)) as revenue_lag_365d'),
                DB::raw('(SELECT AVG(tr2.revenue_target) FROM transaction_revenue tr2 WHERE tr2.report_date BETWEEN DATE_SUB(tr.report_date, INTERVAL 6 DAY) AND tr.report_date) as revenue_rolling_7d_mean'),
                DB::raw('(SELECT AVG(tr2.revenue_target) FROM transaction_revenue tr2 WHERE tr2.report_date BETWEEN DATE_SUB(tr.report_date, INTERVAL 29 DAY) AND tr.report_date) as revenue_rolling_30d_mean'),
            ])
            ->orderBy('tr.report_date')
            ->get();

        $this->info("Computed {$featureRows->count()} feature row(s).");

        $featureBatch = [];
        foreach ($featureRows as $row) {
            $featureBatch[] = [
                'report_date' => $row->report_date,
                'revenue_lag_1d' => $row->revenue_lag_1d,
                'revenue_lag_7d' => $row->revenue_lag_7d,
                'revenue_lag_365d' => $row->revenue_lag_365d,
                'revenue_rolling_7d_mean' => $row->revenue_rolling_7d_mean,
                'revenue_rolling_30d_mean' => $row->revenue_rolling_30d_mean,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Truncate and repopulate features table
        TransactionRevenueFeature::truncate();
        $featureChunks = array_chunk($featureBatch, 500);
        foreach ($featureChunks as $chunk) {
            TransactionRevenueFeature::insert($chunk);
        }

        $this->info("Inserted {$featureRows->count()} row(s) into transaction_revenue_features.");

        return 0;
    }
}