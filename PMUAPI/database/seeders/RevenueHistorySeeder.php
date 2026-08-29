<?php

namespace Database\Seeders;

use App\Models\FeeType;
use App\Models\RevenueHistory;
use App\Models\Stakeholder;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class RevenueHistorySeeder extends Seeder
{
    public function run(): void
    {
        $filePath = 'C:/Users/Marthen Christ/Downloads/PMU-FILE-2020-2025.xlsx';

        if (! file_exists($filePath)) {
            $this->command?->error("File not found: {$filePath}");

            return;
        }

        $this->command?->info('Reading Excel file...');
        $rows = $this->readXlsx($filePath);

        $this->command?->info('Found '.count($rows).' data rows');

        $user = User::first();
        if (! $user) {
            $user = User::create([
                'name' => 'System Importer',
                'email' => 'importer@pmu.gov.ph',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
        }

        $stakeholder = Stakeholder::first();
        if (! $stakeholder) {
            $stakeholder = Stakeholder::create([
                'name' => 'Walk-in',
                'type' => 'buyer',
                'status' => 'active',
            ]);
        }
        $feeTypes = FeeType::all()->keyBy('fee_name');

        foreach ($rows as $row) {
            RevenueHistory::updateOrCreate(
                ['revenue_date' => $row['date']],
                ['total_revenue' => $row['total']]
            );

            $transaction = Transaction::create([
                'stakeholder_id' => $stakeholder?->id,
                'total_amount' => $row['total'],
                'transaction_date' => $row['date'],
                'recorded_by' => $user?->id,
                'remarks' => 'Imported from historical PMU report',
            ]);

            $feeTypeMap = [
                'Usage' => $row['fees'][0] ?? 0,
                'Fish Unloading' => $row['fees'][1] ?? 0,
                'Auxiliary Invoice' => $row['fees'][2] ?? 0,
                'Fish Landing' => ($row['fees'][3] ?? 0) + ($row['fees'][4] ?? 0) + ($row['fees'][5] ?? 0),
                'Inspection' => ($row['fees'][6] ?? 0) + ($row['fees'][7] ?? 0),
                'Wharfage' => ($row['fees'][8] ?? 0) + ($row['fees'][9] ?? 0),
                'Regulatory' => $row['fees'][10] ?? 0,
                'Storage' => $row['fees'][11] ?? 0,
                'Parking' => $row['fees'][12] ?? 0,
                'Rental' => $row['fees'][13] ?? 0,
                'Accreditation' => $row['fees'][14] ?? 0,
                'Entrance' => $row['fees'][16] ?? 0,
            ];

            foreach ($feeTypeMap as $feeName => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                $feeType = $feeTypes->get($feeName);
                if (! $feeType) {
                    continue;
                }

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'fee_type_id' => $feeType->id,
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'subtotal' => $amount,
                ]);
            }
        }

        $this->command?->info('Import completed successfully.');
    }

    private function readXlsx(string $filePath): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException("Unable to open xlsx file: {$filePath}");
        }

        $sharedStrings = [];
        $index = $zip->locateName('xl/sharedStrings.xml');
        if ($index !== false) {
            $xml = simplexml_load_string($zip->getFromIndex($index));
            if ($xml !== false && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    $sharedStrings[] = (string) $si->t;
                }
            }
        }

        $sheetIndex = $zip->locateName('xl/worksheets/sheet1.xml');
        if ($sheetIndex === false) {
            $sheetIndex = $zip->locateName('xl/worksheets/sheet.xml');
        }

        if ($sheetIndex === false) {
            throw new \RuntimeException('No worksheet found in xlsx file');
        }

        $sheetXml = simplexml_load_string($zip->getFromIndex($sheetIndex));
        $zip->close();

        $rows = [];

        if (isset($sheetXml->sheetData->row)) {
            foreach ($sheetXml->sheetData->row as $row) {
                $cells = [];
                foreach ($row->c as $cell) {
                    $cellRef = (string) $cell->attributes()->r;
                    $cellType = (string) $cell->attributes()->t;
                    $valueNode = $cell->v;
                    $value = $valueNode !== null ? (string) $valueNode : '';

                    if ($cellType === 's' && isset($sharedStrings[(int) $value])) {
                        $value = $sharedStrings[(int) $value];
                    }

                    $cells[$cellRef] = $value;
                }

                ksort($cells);
                $values = array_values($cells);
                $firstCell = $values[0] ?? '';

                if (str_starts_with($firstCell, 'FOR THE MONTH OF')) {
                    continue;
                }

                if ($firstCell === 'DATE' || $firstCell === '' || str_starts_with($firstCell, 'Prepared by')) {
                    continue;
                }

                $date = $firstCell;
                if (is_numeric($date)) {
                    $date = $this->excelSerialToDate((int) $date);
                } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
                    $date = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
                }

                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    continue;
                }

                $numericValues = [];
                foreach (array_slice($values, 1) as $value) {
                    $clean = str_replace(',', '', $value);
                    if ($clean !== '' && is_numeric($clean)) {
                        $numericValues[] = (float) $clean;
                    }
                }

                if (empty($numericValues)) {
                    continue;
                }

                $total = array_pop($numericValues);

                if ($total <= 0) {
                    continue;
                }

                $rows[] = [
                    'date' => $date,
                    'total' => $total,
                    'fees' => $numericValues,
                ];
            }
        }

        return $rows;
    }

    private function excelSerialToDate(int $serial): string
    {
        $unixTimestamp = ($serial - 25569) * 86400;

        return date('Y-m-d', (int) $unixTimestamp);
    }
}
