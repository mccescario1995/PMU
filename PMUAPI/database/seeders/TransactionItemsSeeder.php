<?php

namespace Database\Seeders;

use App\Models\FeeType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionItemsSeeder extends Seeder
{
    private const COLUMN_TO_FEE_TYPE = [
        1  => 'Usage',
        2  => 'Fish Unloading',
        3  => 'Auxiliary Invoice',
        4  => 'Auxiliary Invoice',
        5  => 'Regulatory',
        6  => 'Regulatory',
        7  => 'Inspection',
        8  => 'Inspection',
        9  => 'Wharfage',
        10 => 'Inspection',
        11 => 'Storage',
        12 => 'Parking',
        13 => 'Rental',
        14 => 'Accreditation',
        15 => 'Regulatory',
        16 => 'Entrance',
    ];

    public function run(): void
    {
        $filePath = database_path('seeders/PMU-FILE-2020-2025.xlsx');

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

        $feeTypes = FeeType::all()->keyBy('fee_name');

        DB::transaction(function () use ($rows, $user, $feeTypes) {
            DB::table('transaction_items')->delete();
            DB::table('transactions')->delete();

            $batchItems = [];
            $batchSize = 500;
            $transactionCount = 0;
            $itemCount = 0;

            foreach ($rows as $row) {
                $transaction = Transaction::create([
                    'stakeholder_id' => null,
                    'total_amount' => $row['total'],
                    'transaction_date' => $row['date'],
                    'recorded_by' => $user->id,
                    'status' => 'completed',
                    'remarks' => 'Imported from PMU monthly report',
                ]);
                $transactionCount++;

                foreach ($row['fees'] as $colIndex => $amount) {
                    if ($amount <= 0) {
                        continue;
                    }

                    $feeTypeName = self::COLUMN_TO_FEE_TYPE[$colIndex] ?? null;
                    if (! $feeTypeName) {
                        continue;
                    }

                    $feeType = $feeTypes->get($feeTypeName);
                    if (! $feeType) {
                        $this->command?->warn("Fee type not found: {$feeTypeName} (col {$colIndex})");

                        continue;
                    }

                    $batchItems[] = [
                        'transaction_id' => $transaction->id,
                        'fee_type_id' => $feeType->id,
                        'quantity' => 1,
                        'unit_price' => $amount,
                        'subtotal' => $amount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batchItems) >= $batchSize) {
                        DB::table('transaction_items')->insert($batchItems);
                        $itemCount += count($batchItems);
                        $batchItems = [];
                    }
                }

                if ($transactionCount % 500 === 0) {
                    $this->command?->info("Imported {$transactionCount} transactions, {$itemCount} items...");
                }
            }

            if (! empty($batchItems)) {
                DB::table('transaction_items')->insert($batchItems);
                $itemCount += count($batchItems);
            }
        });

        $this->command?->info("Import completed: {$transactionCount} transactions, {$itemCount} items.");
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

        $columnToIndex = function (string $colRef): int {
            $index = 0;
            $colRef = preg_replace('/[0-9]/', '', $colRef);
            for ($i = 0; $i < strlen($colRef); $i++) {
                $index = $index * 26 + (ord($colRef[$i]) - ord('A') + 1);
            }

            return $index - 1;
        };

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

                    $colIndex = $columnToIndex($cellRef);
                    $cells[$colIndex] = $value;
                }

                ksort($cells);

                $firstValue = $cells[0] ?? '';

                if (str_starts_with($firstValue, 'FOR THE MONTH OF')) {
                    continue;
                }

                if ($firstValue === 'DATE' || $firstValue === '' || str_starts_with($firstValue, 'Prepared by')) {
                    continue;
                }

                $date = $firstValue;
                if (is_numeric($date)) {
                    $date = $this->excelSerialToDate((int) $date);
                } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
                    $date = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
                }

                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    continue;
                }

                $fees = [];
                for ($i = 1; $i <= 16; $i++) {
                    $clean = str_replace(',', '', $cells[$i] ?? '');
                    $fees[$i] = ($clean !== '' && is_numeric($clean)) ? (float) $clean : 0;
                }

                $totalClean = str_replace(',', '', $cells[17] ?? '');
                $total = ($totalClean !== '' && is_numeric($totalClean)) ? (float) $totalClean : 0;

                if ($total <= 0) {
                    continue;
                }

                $rows[] = [
                    'date' => $date,
                    'total' => $total,
                    'fees' => $fees,
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
