<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\RevenueHistory;
use App\Models\Stakeholder;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'total_revenue' => (float) RevenueHistory::sum('total_revenue'),
            'transactions_today' => Transaction::whereDate('transaction_date', today())->count(),
            'active_stakeholders' => Stakeholder::where('status', 'active')->count(),
            'low_stock_items' => InventoryItem::where('status', 'low_stock')
                ->orWhere('quantity', '<=', 0)
                ->count(),
        ]);
    }

    public function revenueTrend()
    {
        return response()->json(
            RevenueHistory::orderBy('revenue_date')
                ->get(['revenue_date', 'total_revenue', 'transaction_count'])
        );
    }

    public function revenueBreakdown()
    {
        $rows = DB::table('transaction_items')
            ->join('fee_types', 'fee_types.id', '=', 'transaction_items.fee_type_id')
            ->selectRaw('fee_types.fee_name as source, SUM(transaction_items.subtotal) as amount, COUNT(*) as count')
            ->groupBy('fee_types.fee_name')
            ->get();

        return response()->json($rows);
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

        $pairs = $correlations->filter(fn($r) => $r->rainfall_mm !== null && $r->total_revenue > 0)->values();

        $rainCorr = $this->pearsonCorrelation(
            $pairs->pluck('rainfall_mm')->map(fn($v) => (float) $v),
            $pairs->pluck('total_revenue')->map(fn($v) => (float) $v)
        );

        $tempCorr = $this->pearsonCorrelation(
            $pairs->pluck('temperature')->map(fn($v) => (float) $v),
            $pairs->pluck('total_revenue')->map(fn($v) => (float) $v)
        );

        $windCorr = $this->pearsonCorrelation(
            $pairs->pluck('wind_speed')->map(fn($v) => (float) $v),
            $pairs->pluck('total_revenue')->map(fn($v) => (float) $v)
        );

        return response()->json([
            'data' => $correlations,
            'correlations' => [
                'rainfall' => round($rainCorr, 4),
                'temperature' => round($tempCorr, 4),
                'wind_speed' => round($windCorr, 4),
            ],
        ]);
    }

    private function pearsonCorrelation($x, $y)
    {
        $n = $x->count();
        if ($n < 2) return 0;

        $meanX = $x->avg();
        $meanY = $y->avg();

        $sumXY = $x->zip($y)->sum(fn($pair) => ($pair[0] - $meanX) * ($pair[1] - $meanY));
        $sumX2 = $x->sum(fn($v) => ($v - $meanX) ** 2);
        $sumY2 = $y->sum(fn($v) => ($v - $meanY) ** 2);

        $denominator = sqrt($sumX2 * $sumY2);
        if ($denominator == 0) return 0;

        return $sumXY / $denominator;
    }
}