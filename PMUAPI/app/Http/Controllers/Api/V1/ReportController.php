<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\FeeType;
use App\Models\RevenueHistory;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function daily()
    {
        $date = request('date', today()->toDateString());

        $transactions = Transaction::with(['stakeholder', 'items.feeType'])
            ->whereDate('transaction_date', $date)
            ->get();

        return response()->json([
            'date' => $date,
            'transactions' => TransactionResource::collection($transactions),
            'total' => (float) $transactions->sum('total_amount'),
            'count' => $transactions->count(),
        ]);
    }

    public function dailyExcel()
    {
        $date = request('date', today()->toDateString());

        $transactions = Transaction::with(['stakeholder', 'items.feeType'])
            ->whereDate('transaction_date', $date)
            ->get();

        $callback = function () use ($transactions, $date) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Daily Report - '.$date]);
            fputcsv($handle, ['ID', 'Stakeholder', 'Fee Types', 'Amount', 'Status']);
            foreach ($transactions as $tx) {
                $feeTypes = $tx->items->map(fn ($i) => $i->feeType?->fee_name)->filter()->join(', ');
                fputcsv($handle, [
                    $tx->id,
                    $tx->stakeholder?->name ?? '-',
                    $feeTypes ?: '-',
                    $tx->total_amount,
                    $tx->status,
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Total', '', '', $transactions->sum('total_amount')]);
            fclose($handle);
        };

        return response()->streamDownload($callback, "daily-report-{$date}.csv");
    }

    public function dailyXlsx()
    {
        $date = request('date', today()->toDateString());

        $transactions = Transaction::with(['stakeholder', 'items.feeType'])
            ->whereDate('transaction_date', $date)
            ->get();

        $allFeeTypes = FeeType::orderBy('fee_name')->get(['id', 'fee_name']);

        return $this->generateReportFromTemplate($transactions, 'daily', $date, $allFeeTypes);
    }

    public function monthlyXlsx()
    {
        $month = request('month', now()->format('Y-m'));

        $transactions = Transaction::with(['items.feeType'])
            ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$month])
            ->orderBy('transaction_date')
            ->get();

        $allFeeTypes = FeeType::orderBy('fee_name')->get(['id', 'fee_name']);

        return $this->generateReportFromTemplate($transactions, 'monthly', $month, $allFeeTypes);
    }

    public function annualXlsx()
    {
        $year = request('year', now()->year);

        $transactions = Transaction::with(['items.feeType'])
            ->whereYear('transaction_date', $year)
            ->orderBy('transaction_date')
            ->get();

        $allFeeTypes = FeeType::orderBy('fee_name')->get(['id', 'fee_name']);

        return $this->generateReportFromTemplate($transactions, 'yearly', (string) $year, $allFeeTypes);
    }

    /**
     * Generate report from PMU template
     */
    private function generateReportFromTemplate($transactions, string $type, string $dateOrMonthOrYear, $allFeeTypes = null)
    {
        $templatePath = database_path('seeders/PMU REPORT TEMPLATE.xlsx');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Set report title in D11:M11 (merged)
        $dateObj = \Carbon\Carbon::parse($dateOrMonthOrYear);
        $title = match ($type) {
            'daily' => "FOR THE DAY OF {$dateObj->format('F d, Y')}",
            'monthly' => "FOR THE MONTH OF {$dateObj->format('F Y')}",
            'yearly' => "REPORT FOR THE YEAR {$dateObj->format('Y')}",
            default => "REPORT",
        };
        $sheet->setCellValue('D11', $title);
        $sheet->mergeCells('D11:M11');
        $sheet->getStyle('D11:M11')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Use all fee types from database, not just from transactions
        if ($allFeeTypes) {
            $feeTypes = $allFeeTypes;
        } else {
            // Fallback: collect from transactions
            $feeTypes = $transactions
                ->pluck('items')
                ->flatten()
                ->pluck('feeType')
                ->filter()
                ->unique('id')
                ->sortBy('fee_name')
                ->values();
        }

        // Build dynamic fee type column mapping (B onwards)
        $feeTypeColumns = [];
        $colIndex = 2; // B = 2
        foreach ($feeTypes as $ft) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $feeTypeColumns[$ft->fee_name] = $colLetter;
            $colIndex++;
        }

        // Total column is after the last fee type column
        $totalCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);

        // Set headers in row 13
        $sheet->setCellValue('A13', 'DATE');
        $colIdx = 2;
        foreach ($feeTypes as $ft) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter . '13', $ft->fee_name);
            $colIdx++;
        }
        $sheet->setCellValue($totalCol . '13', 'TOTAL');

        // Apply header formatting: center, wrap text, bold
        $headerRange = "A13:{$totalCol}13";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Set column widths for fee type columns
        foreach ($feeTypeColumns as $feeName => $col) {
            $sheet->getColumnDimension($col)->setWidth(14);
        }
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension($totalCol)->setWidth(14);

        // Data starts at row 14
        $dataStartRow = 14;
        $row = $dataStartRow;
        $grandTotal = 0;
        $colTotals = array_fill_keys(array_keys($feeTypeColumns), 0);

        // Find footer start row BEFORE any row insertions
        $footerStartRow = null;
        $highestRow = $sheet->getHighestRow();
        for ($r = $dataStartRow; $r <= $highestRow; $r++) {
            $cellValue = $sheet->getCell('A' . $r)->getValue();
            if (is_string($cellValue) && stripos($cellValue, 'Prepared by') !== false) {
                $footerStartRow = $r;
                break;
            }
        }

        // If footer not found, default to row 49
        if ($footerStartRow === null) {
            $footerStartRow = 49;
        }

        // Calculate how many data rows we need
        $dataCount = $transactions->count();
        $totalRow = $dataStartRow + $dataCount;
        $footerRows = $highestRow - $footerStartRow + 1;

        // Insert rows at footer position to shift footer down
        if ($dataCount > 0) {
            $sheet->insertNewRowBefore($footerStartRow, $dataCount);
            $totalRow = $footerStartRow - 1; // total row is now right before footer
        }

        foreach ($transactions as $tx) {
            $sheet->setCellValue('A' . $row, $tx->transaction_date->toDateString());

            $isCancelled = strtolower($tx->status) === 'cancelled';
            $includeInTotals = !($type === 'daily' && $isCancelled);

            $rowTotal = 0;
            foreach ($feeTypeColumns as $feeName => $col) {
                $subtotal = $tx->items
                    ->where('feeType.fee_name', '=', $feeName)
                    ->sum('subtotal');
                $sheet->setCellValue($col . $row, $subtotal ?: '');

                if ($includeInTotals) {
                    $rowTotal += $subtotal;
                    $colTotals[$feeName] += $subtotal;
                }
            }

            $sheet->setCellValue($totalCol . $row, $includeInTotals ? ($rowTotal ?: '') : '');

            if ($includeInTotals) {
                $grandTotal += $rowTotal;
            }

            // Highlight cancelled rows
            if ($isCancelled) {
                $sheet->getStyle("A{$row}:{$totalCol}{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FF0000'],
                    ],
                ]);
            }

            // Apply center alignment to data cells
            $sheet->getStyle("A{$row}:{$totalCol}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Add TOTAL row
        $sheet->setCellValue('A' . $totalRow, 'TOTAL');
        foreach ($feeTypeColumns as $feeName => $col) {
            $sheet->setCellValue($col . $totalRow, $colTotals[$feeName] ?: '');
        }
        $sheet->setCellValue($totalCol . $totalRow, $grandTotal);

        // Style total row: bold, centered
        $sheet->getStyle("A{$totalRow}:{$totalCol}{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), "{$type}_report_") . '.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, "{$type}-report-{$dateOrMonthOrYear}.xlsx")->deleteFileAfterSend(true);
    }

    public function dailyPdf()
    {
        $date = request('date', today()->toDateString());

        $transactions = Transaction::with(['stakeholder', 'items.feeType'])
            ->whereDate('transaction_date', $date)
            ->get();

        $pdf = Pdf::loadView('reports.daily', [
            'date' => $date,
            'transactions' => $transactions,
            'total' => (float) $transactions->sum('total_amount'),
            'count' => $transactions->count(),
        ]);

        return $pdf->download("daily-report-{$date}.pdf");
    }

    public function monthly()
    {
        $month = request('month', now()->format('Y-m'));

        $rows = RevenueHistory::whereRaw("DATE_FORMAT(revenue_date, '%Y-%m') = ?", [$month])->get();

        return response()->json([
            'month' => $month,
            'revenue_histories' => $rows,
            'total_revenue' => (float) $rows->sum('total_revenue'),
            'total_transactions' => (int) $rows->sum('transaction_count'),
        ]);
    }

    public function annual()
    {
        $year = request('year', now()->year);

        $rows = RevenueHistory::whereYear('revenue_date', $year)->get();

        return response()->json([
            'year' => (int) $year,
            'rows' => $rows,
            'total_revenue' => (float) $rows->sum('total_revenue'),
            'total_transactions' => (int) $rows->sum('transaction_count'),
        ]);
    }

    public function annualExcel()
    {
        $year = request('year', now()->year);

        $rows = RevenueHistory::whereYear('revenue_date', $year)->get(['revenue_date', 'total_revenue', 'transaction_count']);

        $totalRevenue = (float) $rows->sum('total_revenue');
        $totalTransactions = (int) $rows->sum('transaction_count');

        $callback = function () use ($rows, $year, $totalRevenue, $totalTransactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Yearly Report - '.$year]);
            fputcsv($handle, ['Date', 'Revenue', 'Transactions']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row->revenue_date, $row->total_revenue, $row->transaction_count]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Total Revenue', $totalRevenue]);
            fputcsv($handle, ['Total Transactions', $totalTransactions]);
            fclose($handle);
        };

        return response()->streamDownload($callback, "yearly-report-{$year}.csv");
    }

    // public function annualXlsx()
    // {
    //     $year = request('year', now()->year);

    //     $transactions = Transaction::with(['items.feeType'])
    //         ->whereYear('transaction_date', $year)
    //         ->orderBy('transaction_date')
    //         ->get();

    //     return $this->generateReportFromTemplate($transactions, 'yearly', (string) $year);
    // }

    public function annualPdf()
    {
        $year = request('year', now()->year);

        $rows = RevenueHistory::whereYear('revenue_date', $year)
            ->get(['revenue_date', 'total_revenue', 'transaction_count']);

        $totalRevenue = (float) $rows->sum('total_revenue');
        $totalTransactions = (int) $rows->sum('transaction_count');

        $pdf = Pdf::loadView('reports.annual', [
            'year' => $year,
            'rows' => $rows,
            'totalRevenue' => $totalRevenue,
            'totalTransactions' => $totalTransactions,
        ]);

        return $pdf->download("annual-report-{$year}.pdf");
    }

    public function monthlyPdf()
    {
        $month = request('month', now()->format('Y-m'));

        $rows = RevenueHistory::whereRaw("DATE_FORMAT(revenue_date, '%Y-%m') = ?", [$month])
            ->get(['revenue_date', 'total_revenue', 'transaction_count']);

        $totalRevenue = (float) $rows->sum('total_revenue');
        $totalTransactions = (int) $rows->sum('transaction_count');

        $pdf = Pdf::loadView('reports.monthly', [
            'month' => $month,
            'rows' => $rows,
            'totalRevenue' => $totalRevenue,
            'totalTransactions' => $totalTransactions,
        ]);

        return $pdf->download("monthly-report-{$month}.pdf");
    }

    // public function monthlyXlsx()
    // {
    //     $month = request('month', now()->format('Y-m'));

    //     $transactions = Transaction::with(['items.feeType'])
    //         ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$month])
    //         ->orderBy('transaction_date')
    //         ->get();

    //     return $this->generateReportFromTemplate($transactions, 'monthly', $month);
    // }

    public function monthlyExcel()
    {
        $month = request('month', now()->format('Y-m'));

        $rows = RevenueHistory::whereRaw("DATE_FORMAT(revenue_date, '%Y-%m') = ?", [$month])
            ->get(['revenue_date', 'total_revenue', 'transaction_count']);

        $totalRevenue = (float) $rows->sum('total_revenue');
        $totalTransactions = (int) $rows->sum('transaction_count');

        $callback = function () use ($rows, $month, $totalRevenue, $totalTransactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Monthly Report - '.$month]);
            fputcsv($handle, ['Date', 'Revenue', 'Transactions']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row->revenue_date, $row->total_revenue, $row->transaction_count]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Total Revenue', $totalRevenue]);
            fputcsv($handle, ['Total Transactions', $totalTransactions]);
            fclose($handle);
        };

        return response()->streamDownload($callback, "monthly-report-{$month}.csv");
    }

    /**
     * Fetch transactions with per-fee-type breakdown for a date range.
     *
     * @param  string  $type  daily|monthly|yearly
     */
    public function transactionReport()
    {
        $type = request('type', 'daily');
        $query = Transaction::with(['stakeholder', 'items.feeType']);

        switch ($type) {
            case 'monthly':
                $month = request('month', now()->format('Y-m'));
                $query->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$month]);
                break;
            case 'yearly':
                $year = request('year', now()->year);
                $query->whereYear('transaction_date', $year);
                break;
            case 'daily':
            default:
                $date = request('date', today()->toDateString());
                $query->whereDate('transaction_date', $date);
                $type = 'daily';
                break;
        }

        $transactions = $query->orderBy('transaction_date')->get();

        // Collect all fee types that appear in this range
        $feeTypes = $transactions
            ->pluck('items')
            ->flatten()
            ->pluck('feeType')
            ->filter()
            ->unique('id')
            ->values();

        $rows = $transactions->map(function ($tx) use ($feeTypes) {
            $feeMap = [];
            foreach ($feeTypes as $ft) {
                $feeMap[$ft->id] = $tx->items
                    ->where('fee_type_id', $ft->id)
                    ->sum('subtotal');
            }
            return [
                'id' => str_pad((string) $tx->id, 3, '0', STR_PAD_LEFT),
                'date' => $tx->transaction_date->toDateString(),
                'payor' => $tx->stakeholder?->name ?? '-',
                'fees' => collect($feeTypes)->mapWithKeys(fn ($ft) => [$ft->fee_name => $feeMap[$ft->id] ?? 0])->all(),
                'total' => (float) $tx->total_amount,
                'remarks' => $tx->remarks ?? '',
            ];
        });

        return response()->json([
            'type' => $type,
            'fee_types' => $feeTypes->map(fn ($f) => ['id' => $f->id, 'fee_name' => $f->fee_name]),
            'transactions' => $rows,
            'grand_total' => (float) $transactions->sum('total_amount'),
            'count' => $transactions->count(),
        ]);
    }

    /**
     * Export the per-fee-type transaction report as XLSX.
     *
     * @param  string  $type  daily|monthly|yearly
     */
    public function transactionReportXlsx()
    {
        $type = request('type', 'daily');
        $query = Transaction::with(['stakeholder', 'items.feeType']);

        switch ($type) {
            case 'monthly':
                $month = request('month', now()->format('Y-m'));
                $query->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$month]);
                $label = "monthly-report-{$month}";
                break;
            case 'yearly':
                $year = request('year', now()->year);
                $query->whereYear('transaction_date', $year);
                $label = "yearly-report-{$year}";
                break;
            case 'daily':
            default:
                $date = request('date', today()->toDateString());
                $query->whereDate('transaction_date', $date);
                $type = 'daily';
                $label = "daily-report-{$date}";
                break;
        }

        $transactions = $query->orderBy('transaction_date')->get();

        $feeTypes = $transactions
            ->pluck('items')
            ->flatten()
            ->pluck('feeType')
            ->filter()
            ->unique('id')
            ->values();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Report details
        $sheet->setCellValue('A1', 'PORT MANAGEMENT UNIT - PASACAO, CAMARINES SUR');
        $sheet->setCellValue('A2', 'Transaction Report');
        $sheet->setCellValue('A3', 'Report Period: ' . strtoupper($type));
        $sheet->setCellValue('A4', 'Generated: ' . now()->format('F j, Y g:i A') . ' by: ' . auth()->user()?->name ?? 'System');
        $sheet->setCellValue('A6', 'Date');
        $sheet->setCellValue('B6', 'Transaction #');
        $sheet->setCellValue('C6', 'Payor/Stakeholder');

        $col = 'D';
        foreach ($feeTypes as $ft) {
            $sheet->setCellValue($col . '6', $ft->fee_name);
            $col++;
        }

        $sheet->setCellValue($col . '6', 'Total');
        $col++;
        $sheet->setCellValue($col . '6', 'Remarks');

        $row = 7;
        foreach ($transactions as $tx) {
            $sheet->setCellValue('A' . $row, $tx->transaction_date->toDateString());
            $sheet->setCellValue('B' . $row, str_pad((string) $tx->id, 3, '0', STR_PAD_LEFT));
            $sheet->setCellValue('C' . $row, $tx->stakeholder?->name ?? '-');

            $c = 'D';
            foreach ($feeTypes as $ft) {
                $subtotal = $tx->items->where('fee_type_id', $ft->id)->sum('subtotal');
                $sheet->setCellValue($c . $row, $subtotal);
                $c++;
            }

            $sheet->setCellValue($c . $row, $tx->total_amount);
            $c++;
            $sheet->setCellValue($c . $row, $tx->remarks ?? '');

            if (strtolower($tx->status) === 'cancelled') {
                $lastCol = $c;
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FF0000'],
                    ],
                ]);
            }

            $row++;
        }

        // Total row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue($col . $row, $transactions->sum('total_amount'));

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'transaction_report_') . '.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, $label . '.xlsx')->deleteFileAfterSend(true);
    }
}
