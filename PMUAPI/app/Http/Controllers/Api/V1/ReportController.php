<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\FeeType;
use App\Models\RevenueHistory;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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

    public function dailyPdf()
    {
        $date = request('date', today()->toDateString());

        $transactions = Transaction::with(['stakeholder', 'items.feeType'])
            ->whereDate('transaction_date', $date)
            ->get();

        $feeTypes = FeeType::orderBy('fee_name')->get(['id', 'fee_name']);

        $pdf = Pdf::loadView('reports.daily', [
            'date' => $date,
            'transactions' => $transactions,
            'total' => (float) $transactions->sum('total_amount'),
            'count' => $transactions->count(),
            'feeTypes' => $feeTypes,
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

    public function annualPdf()
    {
        $year = request('year', now()->year);

        $rows = RevenueHistory::whereYear('revenue_date', $year)
            ->get(['revenue_date', 'total_revenue', 'transaction_count']);

        $totalRevenue = (float) $rows->sum('total_revenue');
        $totalTransactions = (int) $rows->sum('transaction_count');

        $feeTypes = FeeType::orderBy('fee_name')->get(['id', 'fee_name']);

        $pdf = Pdf::loadView('reports.annual', [
            'year' => $year,
            'rows' => $rows,
            'totalRevenue' => $totalRevenue,
            'totalTransactions' => $totalTransactions,
            'feeTypes' => $feeTypes,
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

        $feeTypes = FeeType::orderBy('fee_name')->get(['id', 'fee_name']);

        $pdf = Pdf::loadView('reports.monthly', [
            'month' => $month,
            'rows' => $rows,
            'totalRevenue' => $totalRevenue,
            'totalTransactions' => $totalTransactions,
            'feeTypes' => $feeTypes,
        ]);

        return $pdf->download("monthly-report-{$month}.pdf");
    }

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

        $sheet->setCellValue('A1', 'PORT MANAGEMENT UNIT - PASACAO, CAMARINES SUR');
        $sheet->setCellValue('A2', 'Transaction Report');
        $sheet->setCellValue('A3', 'Report Period: '.strtoupper($type));
        $sheet->setCellValue('A4', 'Generated: '.now()->format('F j, Y g:i A').' by: '.auth()->user()?->name ?? 'System');
        $sheet->setCellValue('A6', 'Date');
        $sheet->setCellValue('B6', 'Transaction #');
        $sheet->setCellValue('C6', 'Payor/Stakeholder');

        $col = 'D';
        foreach ($feeTypes as $ft) {
            $sheet->setCellValue($col.'6', $ft->fee_name);
            $col++;
        }

        $sheet->setCellValue($col.'6', 'Total');
        $col++;
        $sheet->setCellValue($col.'6', 'Remarks');

        $row = 7;
        foreach ($transactions as $tx) {
            $sheet->setCellValue('A'.$row, $tx->transaction_date->toDateString());
            $sheet->setCellValue('B'.$row, str_pad((string) $tx->id, 3, '0', STR_PAD_LEFT));
            $sheet->setCellValue('C'.$row, $tx->stakeholder?->name ?? '-');

            $c = 'D';
            foreach ($feeTypes as $ft) {
                $subtotal = $tx->items->where('fee_type_id', $ft->id)->sum('subtotal');
                $sheet->setCellValue($c.$row, $subtotal);
                $c++;
            }

            $sheet->setCellValue($c.$row, $tx->total_amount);
            $c++;
            $sheet->setCellValue($c.$row, $tx->remarks ?? '');

            if (strtolower($tx->status) === 'cancelled') {
                $lastCol = $c;
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FF0000'],
                    ],
                ]);
            }

            $row++;
        }

        $sheet->setCellValue('A'.$row, 'TOTAL');
        $sheet->setCellValue($col.$row, $transactions->sum('total_amount'));

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'transaction_report_').'.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, $label.'.xlsx')->deleteFileAfterSend(true);
    }

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
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();

        $transactions = Transaction::with(['items.feeType'])
            ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$month])
            ->get();

        $dailyData = $transactions->groupBy(
            fn ($tx) => Carbon::parse($tx->transaction_date)->format('Y-m-d')
        );

        // Every day of the selected month, so gaps still appear with blank fees
        $periodLabels = [];
        for ($d = $start->copy(); $d->month === $start->month; $d->addDay()) {
            $periodLabels[$d->format('Y-m-d')] = $d->format('Y-m-d');
        }

        $feeTypes = FeeType::orderBy('fee_name')->get(['id', 'fee_name']);

        return $this->generatePeriodReport(
            $dailyData,
            $feeTypes,
            $periodLabels,
            'MONTHLY REPORT FOR '.strtoupper($start->format('F Y')),
            'PMU REPORT TEMPLATE MONTHLY.xlsx',
            'monthly-report-'.$month
        );
    }

    /**
     * Yearly report - one row per month
     */
    public function annualXlsx()
    {
        $year = (int) request('year', now()->year);

        $transactions = Transaction::with(['items.feeType'])
            ->whereYear('transaction_date', $year)
            ->get();

        $monthlyData = $transactions->groupBy(
            fn ($tx) => Carbon::parse($tx->transaction_date)->format('Y-m')
        );

        // All 12 months, so months without data still appear
        $periodLabels = [];
        for ($m = 1; $m <= 12; $m++) {
            $date = Carbon::create($year, $m, 1);
            $periodLabels[$date->format('Y-m')] = $date->format('F Y');
        }

        $feeTypes = FeeType::orderBy('fee_name')->get(['id', 'fee_name']);

        return $this->generatePeriodReport(
            $monthlyData,
            $feeTypes,
            $periodLabels,
            'ANNUAL REPORT FOR THE YEAR '.$year,
            'PMU REPORT TEMPLATE YEARLY.xlsx',
            'yearly-report-'.$year
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
        $sheet->setCellValue('D11', 'DAILY REPORT FOR  '.$dateObj->format('F d, Y'));
        $sheet->mergeCells('D11:M11');

        $cols = $this->buildColumnMap($feeTypes);
        $totalCol = $cols['total'];

        $this->applyHeaderLabels($sheet, $feeTypes, $cols);
        $this->applyHeaderStyle($sheet, count($feeTypes));
        $this->applyColumnWidths($sheet, $cols);

        $dataStartRow = 14;
        $row = $dataStartRow;
        $grandTotal = 0.0;
        $colTotals = [];

        // Find footer (Prepared by) before shifting anything
        $footerStartRow = $this->findFooterRow($sheet, $dataStartRow) ?? 18;

        $dataCount = $transactions->count();
        $totalRow = $dataStartRow + $dataCount;

        // Shift the footer block down to make room for the data rows
        if ($dataCount > 0) {
            $sheet->insertNewRowBefore($footerStartRow, $dataCount);
        }

        foreach ($transactions as $tx) {
            $sheet->setCellValue('A'.$row, Carbon::parse($tx->transaction_date)->toDateString());

            $status = strtolower((string) $tx->status);
            $isCancelled = $status === 'cancelled';
            $isPending = $status === 'pending';
            $includeInTotals = !($isCancelled || $isPending);

            $rowTotal = 0.0;
            $rowHasValue = false;

            foreach ($feeTypes as $fee) {
                $feeName = $fee->fee_name;
                $subtotal = 0.0;

                foreach ($tx->items as $item) {
                    if ($item->feeType && $item->feeType->fee_name === $feeName) {
                        $subtotal += (float) $item->subtotal;
                    }
                }

                $colTotals[$feeName] = ($colTotals[$feeName] ?? 0.0) + ($includeInTotals ? $subtotal : 0.0);

                if ($subtotal > 0) {
                    $rowHasValue = true;
                    $sheet->getStyle($cols[$feeName].$row)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                $sheet->setCellValue($cols[$feeName].$row, $subtotal > 0 ? $subtotal : '');
                $rowTotal += $includeInTotals ? $subtotal : 0.0;
            }

            if ($includeInTotals && $rowTotal > 0) {
                $grandTotal += $rowTotal;
                $sheet->getStyle($totalCol.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->setCellValue($totalCol.$row, $rowTotal);
            } else {
                $sheet->setCellValue($totalCol.$row, '');
            }

            // Base formatting, then status highlight last so it wins
            $sheet->getStyle("A{$row}:{$totalCol}{$row}")->applyFromArray([
                'font' => ['name' => 'Calibri', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            if ($isCancelled) {
                $sheet->getStyle("A{$row}:{$totalCol}{$row}")->applyFromArray([
                    'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']],
                ]);
            } elseif ($isPending) {
                $sheet->getStyle("A{$row}:{$totalCol}{$row}")->applyFromArray([
                    'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => ['rgb' => '000000']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF99']],
                ]);
            }

            $row++;
        }

        // TOTAL row
        $sheet->setCellValue('A'.$totalRow, 'TOTAL');

        foreach ($feeTypes as $fee) {
            $feeName = $fee->fee_name;
            $value = $colTotals[$feeName] ?? 0.0;
            $sheet->setCellValue($cols[$feeName].$totalRow, $value > 0 ? $value : '');
            $sheet->getStyle($cols[$feeName].$totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $sheet->setCellValue($totalCol.$totalRow, $grandTotal);
        $sheet->getStyle($totalCol.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00');

        $this->applyTotalStyle($sheet, $totalRow, count($feeTypes));

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'daily_report_').'.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, "daily-report-{$date}.xlsx")->deleteFileAfterSend(true);
    }

    /**
     * Generate period report (monthly/yearly - one row per period)
     */
    private function generatePeriodReport($periodData, $feeTypes, array $periodLabels, string $title, string $templateFile, string $fileLabel)
    {
        $templatePath = database_path('seeders/'.$templateFile);
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('D11', $title);
        $sheet->mergeCells('D11:M11');

        $cols = $this->buildColumnMap($feeTypes);
        $totalCol = $cols['total'];

        $this->applyHeaderLabels($sheet, $feeTypes, $cols);
        $this->applyHeaderStyle($sheet, count($feeTypes));
        $this->applyColumnWidths($sheet, $cols);

        $dataStartRow = 14;
        $row = $dataStartRow;
        $grandTotal = 0.0;
        $colTotals = [];

        // Every period gets a row, whether or not it has data. Periods with no
        // qualifying transactions keep their label and leave fee cells blank.
        foreach ($periodLabels as $periodKey => $displayLabel) {
            $transactions = $periodData[$periodKey] ?? collect();

            $sheet->setCellValue('A'.$row, $displayLabel);

            $rowTotal = 0.0;

            foreach ($feeTypes as $fee) {
                $feeName = $fee->fee_name;
                $subtotal = 0.0;

                foreach ($transactions as $tx) {
                    // Monthly/yearly include pending, exclude cancelled
                    if (strtolower((string) $tx->status) === 'cancelled') {
                        continue;
                    }

                    foreach ($tx->items as $item) {
                        if ($item->feeType && $item->feeType->fee_name === $feeName) {
                            $subtotal += (float) $item->subtotal;
                        }
                    }
                }

                $colTotals[$feeName] = ($colTotals[$feeName] ?? 0.0) + $subtotal;
                $rowTotal += $subtotal;

                if ($subtotal > 0) {
                    $sheet->getStyle($cols[$feeName].$row)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                $sheet->setCellValue($cols[$feeName].$row, $subtotal > 0 ? $subtotal : '');
            }

            if ($rowTotal > 0) {
                $grandTotal += $rowTotal;
                $sheet->getStyle($totalCol.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->setCellValue($totalCol.$row, $rowTotal);
            } else {
                $sheet->setCellValue($totalCol.$row, '');
            }

            $sheet->getStyle("A{$row}:{$totalCol}{$row}")->applyFromArray([
                'font' => ['name' => 'Calibri', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $row++;
        }

        // TOTAL row sits directly after the last period row
        $totalRow = $row;
        $sheet->setCellValue('A'.$totalRow, 'TOTAL');

        foreach ($feeTypes as $fee) {
            $feeName = $fee->fee_name;
            $value = $colTotals[$feeName] ?? 0.0;
            $sheet->setCellValue($cols[$feeName].$totalRow, $value > 0 ? $value : '');
            $sheet->getStyle($cols[$feeName].$totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $sheet->setCellValue($totalCol.$totalRow, $grandTotal);
        $sheet->getStyle($totalCol.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00');

        $this->applyTotalStyle($sheet, $totalRow, count($feeTypes));

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), $fileLabel.'_report_').'.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, $fileLabel.'-report.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Build the fee-name => column-letter map once per report.
     *
     * Columns start at B and run in fee_name order; TOTAL follows the last fee
     * column. The map is passed around explicitly so it can never go stale
     * between reports generated in the same PHP process.
     */
    private function buildColumnMap($feeTypes): array
    {
        $map = ['total' => null];

        foreach ($feeTypes->values() as $i => $fee) {
            $map[$fee->fee_name] = Coordinate::stringFromColumnIndex(2 + $i);
        }

        $map['total'] = Coordinate::stringFromColumnIndex(2 + $feeTypes->count());

        return $map;
    }

    private function applyHeaderLabels($sheet, $feeTypes, array $cols): void
    {
        $sheet->setCellValue('A13', 'DATE');

        foreach ($feeTypes as $fee) {
            $sheet->setCellValue($cols[$fee->fee_name] . '13', $fee->fee_name);
        }

        $sheet->setCellValue($cols['total'] . '13', 'TOTAL');
    }

    private function applyColumnWidths($sheet, array $cols): void
    {
        $sheet->getColumnDimension('A')->setWidth(14);

        foreach ($cols as $key => $col) {
            if ($key !== 'total') {
                $sheet->getColumnDimension($col)->setWidth(14);
            }
        }

        $sheet->getColumnDimension($cols['total'])->setWidth(14);
    }

    /**
     * Locate the first row of the signature block. The "Prepared by" text sits
     * in column B of the templates, so scan across the leading columns.
     */
    private function findFooterRow($sheet, $startRow)
    {
        $highestRow = $sheet->getHighestRow();

        for ($r = $startRow; $r <= min($highestRow, $startRow + 60); $r++) {
            for ($c = 1; $c <= 5; $c++) {
                $value = $sheet->getCell(Coordinate::stringFromColumnIndex($c).$r)->getValue();

                if (is_string($value) && stripos($value, 'Prepared by') !== false) {
                    return $r;
                }
            }
        }

        return null;
    }

    private function applyHeaderStyle($sheet, $feeCount)    {
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
                'fillType' => Fill::FILL_SOLID,
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
                'fillType' => Fill::FILL_SOLID,
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