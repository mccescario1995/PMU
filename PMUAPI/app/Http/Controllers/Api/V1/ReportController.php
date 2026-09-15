<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
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

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Daily Report - '.$date);
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A3', 'ID');
        $sheet->setCellValue('B3', 'Stakeholder');
        $sheet->setCellValue('C3', 'Fee Types');
        $sheet->setCellValue('D3', 'Amount');
        $sheet->setCellValue('E3', 'Status');

        $row = 4;
        foreach ($transactions as $tx) {
            $feeTypes = $tx->items->map(fn ($i) => $i->feeType?->fee_name)->filter()->join(', ');
            $sheet->setCellValue('A'.$row, $tx->id);
            $sheet->setCellValue('B'.$row, $tx->stakeholder?->name ?? '-');
            $sheet->setCellValue('C'.$row, $feeTypes ?: '-');
            $sheet->setCellValue('D'.$row, $tx->total_amount);
            $sheet->setCellValue('E'.$row, $tx->status);
            $row++;
        }

        $sheet->setCellValue('A'.($row + 1), 'Total');
        $sheet->setCellValue('D'.($row + 1), $transactions->sum('total_amount'));

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'daily_report_').'.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, "daily-report-{$date}.xlsx")->deleteFileAfterSend(true);
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

    public function annualXlsx()
    {
        $year = request('year', now()->year);

        $rows = RevenueHistory::whereYear('revenue_date', $year)->get(['revenue_date', 'total_revenue', 'transaction_count']);

        $totalRevenue = (float) $rows->sum('total_revenue');
        $totalTransactions = (int) $rows->sum('transaction_count');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Yearly Report - '.$year);
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A3', 'Date');
        $sheet->setCellValue('B3', 'Revenue');
        $sheet->setCellValue('C3', 'Transactions');

        $row = 4;
        foreach ($rows as $r) {
            $sheet->setCellValue('A'.$row, $r->revenue_date);
            $sheet->setCellValue('B'.$row, $r->total_revenue);
            $sheet->setCellValue('C'.$row, $r->transaction_count);
            $row++;
        }

        $sheet->setCellValue('A'.($row + 1), 'Total Revenue');
        $sheet->setCellValue('B'.($row + 1), $totalRevenue);
        $sheet->setCellValue('C'.($row + 1), $totalTransactions);

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'yearly_report_').'.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, "yearly-report-{$year}.xlsx")->deleteFileAfterSend(true);
    }

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

    public function monthlyXlsx()
    {
        $month = request('month', now()->format('Y-m'));

        $rows = RevenueHistory::whereRaw("DATE_FORMAT(revenue_date, '%Y-%m') = ?", [$month])
            ->get(['revenue_date', 'total_revenue', 'transaction_count']);

        $totalRevenue = (float) $rows->sum('total_revenue');
        $totalTransactions = (int) $rows->sum('transaction_count');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Monthly Report - '.$month);
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A3', 'Date');
        $sheet->setCellValue('B3', 'Revenue');
        $sheet->setCellValue('C3', 'Transactions');

        $row = 4;
        foreach ($rows as $r) {
            $sheet->setCellValue('A'.$row, $r->revenue_date);
            $sheet->setCellValue('B'.$row, $r->total_revenue);
            $sheet->setCellValue('C'.$row, $r->transaction_count);
            $row++;
        }

        $sheet->setCellValue('A'.($row + 1), 'Total Revenue');
        $sheet->setCellValue('B'.($row + 1), $totalRevenue);
        $sheet->setCellValue('C'.($row + 1), $totalTransactions);

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'monthly_report_').'.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, "monthly-report-{$month}.xlsx")->deleteFileAfterSend(true);
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

        $sheet->setCellValue($col, 'Total');
        $col++;
        $sheet->setCellValue($col, 'Remarks');

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

            $sheet->setCellValue($c, $tx->total_amount);
            $c++;
            $sheet->setCellValue($c, $tx->remarks ?? '');
            $row++;
        }

        // Total row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue($col, $transactions->sum('total_amount'));

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'transaction_report_') . '.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, $label . '.xlsx')->deleteFileAfterSend(true);
    }
}
