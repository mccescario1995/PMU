<?php

namespace Database\Seeders;

use App\Models\MlFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MlFeatureSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Portable relative path (works on both local Windows and Hostinger Linux)
        $filePath = database_path('seeders/data-2026-09-12-17-56-22.csv');

        if (! file_exists($filePath)) {
            $this->command?->error("CSV File not found at: {$filePath}");
            return;
        }

        $this->command?->info('Reading CSV file for ML features...');

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->command?->error('Unable to open CSV file.');
            return;
        }

        $header = fgetcsv($handle);
        if ($header !== false && count($header) > 0) {
            $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
        }
        $batchSize = 500;
        $batch = [];
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 18) {
                continue;
            }

            $data = array_combine($header, $row);

            $batch[] = [
                'report_date'              => $data['report_date'],
                'year_num'                 => (int) $data['year_num'],
                'month_num'                => (int) $data['month_num'],
                'day_num'                  => (int) $data['day_num'],
                'day_of_week'              => (int) $data['day_of_week'],
                'quarter_num'              => (int) $data['quarter_num'],
                'is_weekend'               => in_array(strtolower($data['is_weekend']), ['true', '1'], true),
                'is_month_start'           => in_array(strtolower($data['is_month_start']), ['true', '1'], true),
                'is_month_end'             => in_array(strtolower($data['is_month_end']), ['true', '1'], true),
                'revenue_target'           => (float) $data['revenue_target'],
                'log_revenue'              => is_numeric($data['log_revenue']) ? (float) $data['log_revenue'] : 0.0,
                'revenue_lag_1d'           => is_numeric($data['revenue_lag_1d']) ? (float) $data['revenue_lag_1d'] : 0.0,
                'revenue_lag_7d'           => is_numeric($data['revenue_lag_7d']) ? (float) $data['revenue_lag_7d'] : 0.0,
                'revenue_lag_365d'         => is_numeric($data['revenue_lag_365d']) ? (float) $data['revenue_lag_365d'] : 0.0,
                'revenue_rolling_7d_mean'  => is_numeric($data['revenue_rolling_7d_mean']) ? (float) $data['revenue_rolling_7d_mean'] : 0.0,
                'revenue_rolling_30d_mean' => is_numeric($data['revenue_rolling_30d_mean']) ? (float) $data['revenue_rolling_30d_mean'] : 0.0,
                'summary_metric_col17'     => is_numeric($data['summary_metric_col17']) ? (float) $data['summary_metric_col17'] : 0.0,
                'is_missing_date'          => in_array(strtolower($data['is_missing_date']), ['true', '1'], true),
                'updated_at'               => now(),
                'created_at'               => now(),
            ];

            if (count($batch) >= $batchSize) {
                DB::table('ml_features')->insertOrIgnore($batch);
                $count += count($batch);
                $this->command?->info("Imported {$count} rows...");
                $batch = [];
            }
        }

        if (! empty($batch)) {
            DB::table('ml_features')->insertOrIgnore($batch);
            $count += count($batch);
        }

        fclose($handle);
        $this->command?->info("ML features import completed: {$count} rows into table 'ml_features'.");
    }
}
