<?php

namespace Database\Seeders;

use App\Models\RevenueHistory;
use Illuminate\Database\Seeder;

class RevenueHistorySeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('seeders/revenue_histories_latest.csv');

        if (! file_exists($filePath)) {
            $this->command?->error("File not found: {$filePath}");

            return;
        }

        $this->command?->info('Reading CSV file...');
        $rows = $this->readCsv($filePath);

        $this->command?->info('Found '.count($rows).' data rows');

        foreach ($rows as $row) {
            RevenueHistory::updateOrCreate(
                ['revenue_date' => $row['revenue_date']],
                ['total_revenue' => $row['total_revenue']]
            );
        }

        $this->command?->info('Import completed successfully.');
    }

    private function readCsv(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open CSV file: {$filePath}");
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return $rows;
        }

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < count($headers)) {
                continue;
            }

            $row = array_combine($headers, $data);

            $rows[] = [
                'revenue_date' => $row['revenue_date'],
                'total_revenue' => (float) $row['total_revenue'],
            ];
        }

        fclose($handle);

        return $rows;
    }
}
