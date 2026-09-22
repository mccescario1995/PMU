<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Stakeholder;
use App\Models\Transaction;
use App\Models\TransactionRevenue;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $latestWeather = DB::table('weather_data')
            ->orderByDesc('weather_date')
            ->first(['weather_date', 'temperature', 'rainfall_mm', 'wind_speed']);

        return response()->json([
            'today_revenue' => (float) TransactionRevenue::whereDate('report_date', today())->sum('revenue_target'),
            'monthly_revenue' => (float) TransactionRevenue::where('year_num', now()->subMonth()->year)
                ->where('month_num', now()->subMonth()->month)->sum('revenue_target'),
            'yearly_revenue' => (float) TransactionRevenue::where('year_num', now()->subYear())->sum('revenue_target'),
            'transactions_today' => Transaction::whereDate('transaction_date', today())->count(),
            'active_stakeholders' => Stakeholder::where('status', 'active')->count(),
            'low_stock_items' => InventoryItem::where(function ($q) {
                $q->whereRaw('quantity <= minimum_stock')
                  ->orWhere('status', 'damaged');
            })->count(),
            'latest_weather' => $latestWeather ? [
                'weather_date' => $latestWeather->weather_date,
                'temperature' => (float) $latestWeather->temperature,
                'rainfall_mm' => (float) $latestWeather->rainfall_mm,
                'wind_speed' => (float) $latestWeather->wind_speed,
            ] : null,
        ]);
    }

    public function revenueTrend(Request $request)
    {
        $limit = (int) $request->query('limit', 500);
        $limit = min($limit, 1000);

        return response()->json(
            RevenueHistory::orderBy('revenue_date')
                ->take($limit)
                ->get(['revenue_date', 'total_revenue', 'transaction_count'])
        );
    }

    public function revenueBreakdown(Request $request)
    {
        $year = $request->query('year');

        $query = DB::table('transaction_items')
            ->join('fee_types', 'fee_types.id', '=', 'transaction_items.fee_type_id')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->selectRaw('fee_types.fee_name as source, SUM(transaction_items.subtotal) as amount, COUNT(*) as count');

        if ($year) {
            $query->whereYear('transactions.transaction_date', $year);
        }

        $rows = $query->groupBy('fee_types.fee_name')->get();

        return response()->json($rows);
    }

    public function exportTransactionReport(Request $request)
    {
        $type = $request->query('type');
        $date = $request->query('date');
        $month = $request->query('month');
        $year = $request->query('year');

        $query = DB::table('transaction_items')
            ->join('fee_types', 'fee_types.id', '=', 'transaction_items.fee_type_id')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id');

        if ($type === 'daily' && $date) {
            $query->whereDate('transactions.transaction_date', $date);
        } elseif ($type === 'monthly' && $month) {
            $query->whereMonth('transactions.transaction_date', $month);
        } elseif ($type === 'yearly' && $year) {
            $query->whereYear('transactions.transaction_date', $year);
        }

        $query->selectRaw('fee_types.fee_name as source, SUM(transaction_items.subtotal) as amount, COUNT(*) as count');

        $rows = $query->groupBy('fee_types.fee_name')->get();

        // Generate Excel file using PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Transaction Report');

        // Add headers
        $sheet->setCellValue('A1', 'Fee Type');
        $sheet->setCellValue('B1', 'Total Amount');
        $sheet->setCellValue('C1', 'Transaction Count');

        // Add data
        $row = 2;
        foreach ($rows as $rowData) {
            $sheet->setCellValue('A' . $row, $rowData->source);
            $sheet->setCellValue('B' . $row, $rowData->amount);
            $sheet->setCellValue('C' . $row, $rowData->count);
            $row++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);

        // Generate filename
        $filename = match ($type) {
            'daily' => "daily-report-" . $date . ".xlsx",
            'monthly' => "monthly-report-" . $month . ".xlsx",
            'yearly' => "yearly-report-" . $year . ".xlsx",
            default => "transaction-report.xlsx"
        };

        // Return as Excel file download
        $headers = [
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition: attachment; filename="' . $filename . '"',
            'Cache-Control: max-age=0',
        ];

        $writer = new Xlsx($spreadsheet);
        $response = $writer->stream('php://output');
        foreach ($headers as $header) {
            $response->header(explode(':', $header)[0], explode(':', $header)[1]);
        }

        return $response;
    }

    public function inventorySummary()
    {
        return response()->json([
            'total_items' => InventoryItem::count(),
            'by_status' => InventoryItem::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'by_category_type' => InventoryItem::selectRaw('category_type, COUNT(*) as count')
                ->groupBy('category_type')
                ->get(),
        ]);
    }

    public function weatherRevenueCorrelation()
    {
        $correlations = DB::table('revenue_histories')
            ->leftJoin('weather_data', 'weather_data.weather_date', '=', 'revenue_histories.revenue_date')
            ->selectRaw('revenue_histories.revenue_date, revenue_histories.total_revenue, weather_data.rainfall_mm, weather_data.wind_speed, weather_data.temperature')
            ->orderBy('revenue_histories.revenue_date')
            ->get();

        $pairs = $correlations->filter(fn ($r) => $r->rainfall_mm !== null && $r->total_revenue > 0)->values();

        $rainCorr = $this->pearsonCorrelation(
            $pairs->pluck('rainfall_mm')->map(fn ($v) => (float) $v),
            $pairs->pluck('total_revenue')->map(fn ($v) => (float) $v)
        );

        $tempCorr = $this->pearsonCorrelation(
            $pairs->pluck('temperature')->map(fn ($v) => (float) $v),
            $pairs->pluck('total_revenue')->map(fn ($v) => (float) $v)
        );

        $windCorr = $this->pearsonCorrelation(
            $pairs->pluck('wind_speed')->map(fn ($v) => (float) $v),
            $pairs->pluck('total_revenue')->map(fn ($v) => (float) $v)
        );

        return response()->json([
            'correlations' => [
                'rainfall' => round($rainCorr, 4),
                'temperature' => round($tempCorr, 4),
                'wind_speed' => round($windCorr, 4),
                'data_points' => $pairs->count(),
            ],
        ]);
    }

    private function pearsonCorrelation($x, $y)
    {
        $n = $x->count();
        if ($n < 2) {
            return 0;
        }

        $meanX = $x->avg();
        $meanY = $y->avg();

        $sumXY = $x->zip($y)->sum(fn ($pair) => ($pair[0] - $meanX) * ($pair[1] - $meanY));
        $sumX2 = $x->sum(fn ($v) => ($v - $meanX) ** 2);
        $sumY2 = $y->sum(fn ($v) => ($v - $meanY) ** 2);

        $denominator = sqrt($sumX2 * $sumY2);
        if ($denominator == 0) {
            return 0;
        }

        return $sumXY / $denominator;
    }
}