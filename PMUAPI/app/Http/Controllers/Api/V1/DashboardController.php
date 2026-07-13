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
        ]);
    }
}
