<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ImportLog;
use App\Models\RevenueHistory;
use App\Models\Stakeholder;
use App\Models\WeatherData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function index()
    {
        return response()->json(
            ImportLog::orderByDesc('created_at')->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|string|in:revenue_histories,weather_data,stakeholders',
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
        ]);

        $entityType = $request->input('entity_type');
        $file = $request->file('file');

        $path = $file->storeAs('imports', $file->getClientOriginalName());

        $log = ImportLog::create([
            'entity_type' => $entityType,
            'filename' => $file->getClientOriginalName(),
            'status' => 'processing',
        ]);

        try {
            $rows = $this->readFile($path);
            $result = match ($entityType) {
                'revenue_histories' => $this->importRevenueHistories($rows),
                'weather_data' => $this->importWeatherData($rows),
                'stakeholders' => $this->importStakeholders($rows),
            };

            $log->update([
                'total_rows' => $result['total'],
                'imported_rows' => $result['imported'],
                'skipped_rows' => $result['skipped'],
                'errors' => $result['errors'] ?: null,
                'status' => $result['skipped'] > 0 && empty($result['errors']) ? 'partial' : 'completed',
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'errors' => [$e->getMessage()],
            ]);

            throw $e;
        } finally {
            Storage::delete($path);
        }

        return response()->json($log, 201);
    }

    private function readFile(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'xlsx') {
            return $this->readXlsx($path);
        }

        return $this->readCsv($path);
    }

    private function readCsv(string $path): array
    {
        $handle = fopen(Storage::path($path), 'r');
        $header = fgetcsv($handle);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_pad($row, count($header), '');
            $rows[] = array_combine($header, $row);
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsx(string $path): array
    {
        $spreadsheet = IOFactory::load(Storage::path($path));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];
        $header = [];
        $highestColumnIndex = $sheet->getHighestColumnIndex();

        foreach ($sheet->getRowIterator() as $index => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $values = [];
            foreach ($cellIterator as $cell) {
                $values[] = $cell->getValue();
            }

            if ($index === 1) {
                $header = $values;

                continue;
            }

            $values = array_pad($values, count($header), '');
            $rows[] = array_combine($header, $values);
        }

        return $rows;
    }

    private function importRevenueHistories(array $rows): array
    {
        $total = 0;
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            $total++;

            if (! $row || empty($row['revenue_date']) || ! isset($row['total_revenue'])) {
                $skipped++;
                $errors[] = "Row {$total}: missing required fields";

                continue;
            }

            RevenueHistory::updateOrCreate(
                ['revenue_date' => $row['revenue_date']],
                [
                    'total_revenue' => $row['total_revenue'] ?? 0,
                    'transaction_count' => $row['transaction_count'] ?? 0,
                ]
            );

            $imported++;
        }

        return compact('total', 'imported', 'skipped', 'errors');
    }

    private function importWeatherData(array $rows): array
    {
        $total = 0;
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            $total++;

            if (! $row || empty($row['weather_date'])) {
                $skipped++;
                $errors[] = "Row {$total}: missing weather_date";

                continue;
            }

            WeatherData::updateOrCreate(
                ['weather_date' => $row['weather_date']],
                [
                    'rainfall_mm' => $row['rainfall_mm'] ?? null,
                    'wind_speed' => $row['wind_speed'] ?? null,
                    'temperature' => $row['temperature'] ?? null,
                ]
            );

            $imported++;
        }

        return compact('total', 'imported', 'skipped', 'errors');
    }

    private function importStakeholders(array $rows): array
    {
        $total = 0;
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            $total++;

            if (! $row || empty($row['name']) || empty($row['type'])) {
                $skipped++;
                $errors[] = "Row {$total}: missing name or type";

                continue;
            }

            $type = strtolower($row['type']);
            $allowed = ['buyer', 'broker', 'renter'];

            if (! in_array($type, $allowed)) {
                $skipped++;
                $errors[] = "Row {$total}: invalid type '{$type}'";

                continue;
            }

            Stakeholder::updateOrCreate(
                ['name' => $row['name']],
                [
                    'type' => $type,
                    'contact_no' => $row['contact_no'] ?? null,
                    'email' => $row['email'] ?? null,
                    'address' => $row['address'] ?? null,
                    'status' => $row['status'] ?? 'active',
                ]
            );

            $imported++;
        }

        return compact('total', 'imported', 'skipped', 'errors');
    }
}
