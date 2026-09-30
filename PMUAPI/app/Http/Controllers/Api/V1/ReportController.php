<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FeeType;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    /**
     * Daily report with per-transaction rows
     */
    public function dailyXlsx()
    {
        $date = request('date', today()->toDateString());

        $transactions = Transaction::with(['stakeholder', 'items.feeType'])
            ->whereDate('transaction_date', $date)
            ->get();

        $feeTypes = FeeType::orderBy('fee_name')->get(['id', 'fee_name']);

        return $this->generateDailyReport($transactions, $date, $feeTypes);
    }

    /**
     * Monthly report - one row per day
     */
    public function monthlyXlsx()
    {
        $month = request('month', now()->format('Y-m'));

        // Get all transactions in the month, group by day
        $dailyData = Transaction::with(['items.feeType'])
            ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$month])
            ->get()
            ->groupBy(function ($tx) {
                return Carbon::parse($tx->transaction_date)->format('Y-m-d');
            });

        $feeTypes = FeeType::orderBy('fee_name')->get(['id', 'fee_name']);
        $periodLabel = Carbon::parse($month . '-01')->format('F Y'); // e.g. "November 2026"

        return $this->generatePeriodReport(
            $dailyData,
            $feeTypes,
            $periodLabel,
            'MONTHLY REPORT FOR ', // template prefix
            31 // fixed days
        );
    }

    /**
     * Yearly report - one row per month
     */
    public function annualXlsx()
    {
        $year = request('year', now()->year);

        // Get all transactions in the year, group by month
        $monthlyData = Transaction::with(['items.feeType'])
            ->whereYear('transaction_date', $year)
            ->get()
            ->groupBy(function ($tx) {
                return Carbon::parse($tx->transaction_date)->format('Y-m');
            });

        $feeTypes = FeeType::orderBy('fee_name')->get(['id', 'fee_name']);
        $periodLabel = $year; // just the year

        return $this->generatePeriodReport(
            $monthlyData,
            $feeTypes,
            $periodLabel,
            'ANNUAL REPORT FOR THE YEAR ', // template prefix
            12 // fixed months
        );
    }

    /**
     * Generate daily report (per-transaction rows with highlighting)
     */
    private function generateDailyReport($transactions, $date, $feeTypes)
    {
        $templatePath = database_path('seeders/PMU REPORT TEMPLATE DAILY.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Update title - replace date in "DAILY REPORT FOR  JANUARY 31, 2026"
        $dateObj = Carbon::parse($date);
        $newTitle = 'DAILY REPORT FOR  ' . $dateObj->format('F d, Y');
        $sheet->setCellValue('D11', $newTitle);
        $sheet->mergeCells('D11:M11');
        $this->applyHeaderStyle($sheet, count($feeTypes));

        $dataStartRow = 14;
        $row = $dataStartRow;
        $grandTotal = 0;
        $colTotals = array_fill_keys(array_keys($feeTypes->pluck('fee_name')->toArray()), 0);

        // Find footer (Prepared by) - should be around row 18 in template
        $footerStartRow = $this->findFooterRow($sheet, $dataStartRow);
        if ($footerStartRow === null) {
            $footerStartRow = 18; // fallback from inspection
        }

        // Insert rows for data, shifting footer down
        $dataCount = $transactions->count();
        if ($dataCount > 0) {
            $sheet->insertNewRowBefore($footerStartRow, $dataCount);
        }

        foreach ($transactions as $tx) {
            $sheet->setCellValue('A' . $row, $tx->transaction_date->toDateString());

            $status = strtolower((string) $tx->status);
            $isCancelled = $status === 'cancelled';
            $isPending = $status === 'pending';
            $includeInTotals = !($isCancelled || $isPending); // daily excludes both

            $rowTotal = 0;
            foreach ($feeTypes as $fee) {
                $feeName = $fee->fee_name;
                $col = $this->getColumnLetterForFee($feeName, $feeTypes);
                $subtotal = $tx->items
                    ->where('feeType.fee_name', '=', $feeName)
                    ->sum('subtotal');
                $sheet->setCellValue($col . $row, $subtotal ?: '');

                if ($includeInTotals) {
                    $rowTotal += $subtotal;
                    $colTotals[$feeName] += $subtotal;
                }
            }

            $sheet->setCellValue($this->getTotalColumnLetter($feeTypes) . $row, $includeInTotals ? ($rowTotal ?: '') : '');

            if ($includeInTotals) {
                $grandTotal += $rowTotal;
            }

            // Base formatting
            $sheet->getStyle("A{$row}:{$this->getTotalColumnLetter($feeTypes)}{$row}")->applyFromArray([
                'font' => ['name' => 'Calibri', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // Status highlighting (applied last)
            if ($isCancelled) {
                $sheet->getStyle("A{$row}:{$this->getTotalColumnLetter($feeTypes)}{$row}")->applyFromArray([
                    'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']],
                ]);
            } elseif ($isPending) {
                $sheet->getStyle("A{$row}:{$this->getTotalColumnLetter($feeTypes)}{$row}")->applyFromArray([
                    'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => ['rgb' => '000000']],
                    'fill' => [Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF99']],
                ]);
            }

            $row++;
        }

        // Add TOTAL row
        $totalRow = $dataStartRow + $dataCount;
        $sheet->setCellValue('A' . $totalRow, 'TOTAL');
        foreach ($feeTypes as $fee) {
            $feeName = $fee->fee_name;
            $col = $this->getColumnLetterForFee($feeName, $feeTypes);
            $sheet->setCellValue($col . $totalRow, $colTotals[$feeName] ?: '');
        }
        $sheet->setCellValue($this->getTotalColumnLetter($feeTypes) . $totalRow, $grandTotal);

        // Style TOTAL row like header
        $this->applyTotalStyle($sheet, $totalRow, $feeTypes);

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'daily_report_') . '.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, "daily-report-{$date}.xlsx")->deleteFileAfterSend(true);
    }

    /**
     * Generate period report (monthly/yearly - one row per period)
     */
    private function generatePeriodReport($periodData, $feeTypes, $periodLabel, $templatePrefix, $periodCount)
    {
        // Find correct template based on prefix
        $templateMap = [
            'MONTHLY REPORT FOR ' => 'PMU REPORT TEMPLATE MONTHLY.xlsx',
            'ANNUAL REPORT FOR THE YEAR ' => 'PMU REPORT TEMPLATE YEARLY.xlsx',
        ];
        $templateFile = $templateMap[$templatePrefix] ?? 'PMU REPORT TEMPLATE MONTHLY.xlsx';
        $templatePath = database_path('seeders/' . $templateFile);

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Update title
        $newTitle = $templatePrefix . $periodLabel;
        $sheet->setCellValue('D11', $newTitle);
        $sheet->mergeCells('D11:M11');
        $this->applyHeaderStyle($sheet, count($feeTypes));

        $dataStartRow = 14;
        $row = $dataStartRow;
        $grandTotal = 0;
        $colTotals = array_fill_keys(array_keys($feeTypes->pluck('fee_name')->toArray()), 0);

        // Find footer (Prepared by)
        $footerStartRow = $this->findFooterRow($sheet, $dataStartRow);
        if ($footerStartRow === null) {
            // fallback from inspection
            $footerStartRow = $templatePrefix === 'MONTHLY REPORT FOR ' ? 48 : 29;
        }

        // No row insertion needed - templates already have blank rows for all periods
        // Monthly: rows 14-44 (31 rows), Yearly: rows 14-25 (12 rows)
        // Footer stays put, we just fill in the data rows

        foreach ($periodData as $periodLabel => $transactions) {
            $sheet->setCellValue('A' . $row, $periodLabel);

            $rowTotal = 0;
            foreach ($feeTypes as $fee) {
                $feeName = $fee->fee_name;
                $col = $this->getColumnLetterForFee($feeName, $feeTypes);
                $subtotal = 0;
                foreach ($transactions as $tx) {
                    $status = strtolower((string) $tx->status);
                    $isCancelled = $status === 'cancelled';
                    $isPending = $status === 'pending';
                    // Monthly/yearly: INCLUDE pending in computation, exclude cancelled only
                    if (!$isCancelled) {
                        $subtotal += $tx->items
                            ->where('feeType.fee_name', '=', $feeName)
                            ->sum('subtotal');
                    }
                }
                $sheet->setCellValue($col . $row, $subtotal ?: '');

                $rowTotal += $subtotal;
                $colTotals[$feeName] += $subtotal;
            }

            $sheet->setCellValue($this->getTotalColumnLetter($feeTypes) . $row, $rowTotal ?: '');

            if ($rowTotal > 0) {
                $grandTotal += $rowTotal;
            }

            // Base formatting
            $sheet->getStyle("A{$row}:{$this->getTotalColumnLetter($feeTypes)}{$row}")->applyFromArray([
                'font' => ['name' => 'Calibri', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $row++;
        }

        // Add TOTAL row (after last data row)
        $totalRow = $dataStartRow + $periodCount;
        $sheet->setCellValue('A' . $totalRow, 'TOTAL');
        foreach ($feeTypes as $fee) {
            $feeName = $fee->fee_name;
            $col = $this->getColumnLetterForFee($feeName, $feeTypes);
            $sheet->setCellValue($col . $totalRow, $colTotals[$feeName] ?: '');
        }
        $sheet->setCellValue($this->getTotalColumnLetter($feeTypes) . $totalRow, $grandTotal);

        // Style TOTAL row like header
        $this->applyTotalStyle($sheet, $totalRow, $feeTypes);

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), strtolower(str_replace(' ', '_', $templatePrefix)) . '_report_') . '.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, strtolower(str_replace(' ', '-', $templatePrefix)) . "-report-{$periodLabel}.xlsx")->deleteFileAfterSend(true);
    }

    private function findFooterRow($sheet, $startRow)
    {
        $highestRow = $sheet->getHighestRow();
        for ($r = $startRow; $r <= min($highestRow, $startRow + 50); $r++) {
            $cellValue = $sheet->getCell('A' . $r)->getValue();
            if (is_string($cellValue) && stripos($cellValue, 'Prepared by') !== false) {
                return $r;
            }
        }
        return null;
    }

    private function getColumnLetterForFee($feeName, $feeTypes)
    {
        static $cache = [];
        if (!isset($cache[$feeName])) {
            $index = 2; // B = 2
            foreach ($feeTypes as $fee) {
                if ($fee->fee_name === $feeName) {
                    break;
                }
                $index++;
            }
            $cache[$feeName] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
        }
        return $cache[$feeName];
    }

    private function getTotalColumnLetter($feeTypes)
    {
        $colIndex = 2 + $feeTypes->count(); // B + number of fee types
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
    }

    private function applyHeaderStyle($sheet, $feeCount)
    {
        $totalCol = $this->getTotalColumnLetterFromCount($feeCount);
        $headerRange = "A13:{$totalCol}13";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'size' => 11,
                'bold' => true,
                'color' => ['rgb' => 'FF0000'],
            ],
            'fill' => [
                Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(13)->setRowHeight(45);
    }

    private function applyTotalStyle($sheet, $row, $feeCount)
    {
        $totalCol = $this->getTotalColumnLetterFromCount($feeCount);
        $sheet->getStyle("A{$row}:{$totalCol}{$row}")->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'size' => 11,
                'bold' => true,
                'color' => ['rgb' => 'FF0000'],
            ],
            'fill' => [
                Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function getTotalColumnLetterFromCount($feeCount)
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $feeCount);
    }
}