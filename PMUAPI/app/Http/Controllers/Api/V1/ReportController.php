<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\RevenueHistory;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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
            fputcsv($handle, ['Daily Report - ' . $date]);
            fputcsv($handle, ['ID', 'Stakeholder', 'Fee Types', 'Amount', 'Status']);
            foreach ($transactions as $tx) {
                $feeTypes = $tx->items->map(fn($i) => $i->feeType?->fee_name)->filter()->join(', ');
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
            fputcsv($handle, ['Yearly Report - ' . $year]);
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

    public function monthlyExcel()
    {
        $month = request('month', now()->format('Y-m'));

        $rows = RevenueHistory::whereRaw("DATE_FORMAT(revenue_date, '%Y-%m') = ?", [$month])
            ->get(['revenue_date', 'total_revenue', 'transaction_count']);

        $totalRevenue = (float) $rows->sum('total_revenue');
        $totalTransactions = (int) $rows->sum('transaction_count');

        $callback = function () use ($rows, $month, $totalRevenue, $totalTransactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Monthly Report - ' . $month]);
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
}
