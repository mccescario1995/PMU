<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TransactionRevenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionRevenueController extends Controller
{
    public function index()
    {
        return TransactionRevenue::orderBy('report_date')->paginate(request('per_page', 50));
    }

    public function show(string $date)
    {
        return TransactionRevenue::where('report_date', $date)->firstOrFail();
    }

    public function mlData(Request $request)
    {
        $secret = config('services.pmuml.secret');
        $provided = (string) $request->header('X-PMUML-Secret', '');

        if (! $secret || ! hash_equals((string) $secret, $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $rows = DB::table('transaction_revenue as tr')
            ->leftJoin('transaction_revenue_features as trf', 'trf.report_date', '=', 'tr.report_date')
            ->whereBetween('tr.report_date', [$data['start_date'], $data['end_date']])
            ->orderBy('tr.report_date')
            ->select([
                'tr.report_date',
                'tr.year_num',
                'tr.month_num',
                'tr.day_num',
                'tr.day_of_week',
                'tr.quarter_num',
                'tr.is_weekend',
                'tr.is_month_start',
                'tr.is_month_end',
                'tr.revenue_target',
                'tr.log_revenue',
                'tr.temp_celsius',
                'tr.precipitation_mm',
                'tr.wind_speed',
                'trf.revenue_lag_1d',
                'trf.revenue_lag_7d',
                'trf.revenue_lag_365d',
                'trf.revenue_rolling_7d_mean',
                'trf.revenue_rolling_30d_mean',
            ])
            ->get()
            ->map(fn ($row) => [
                'report_date' => $row->report_date,
                'year_num' => (int) $row->year_num,
                'month_num' => (int) $row->month_num,
                'day_num' => (int) $row->day_num,
                'day_of_week' => (int) $row->day_of_week,
                'quarter_num' => (int) $row->quarter_num,
                'is_weekend' => (bool) $row->is_weekend,
                'is_month_start' => (bool) $row->is_month_start,
                'is_month_end' => (bool) $row->is_month_end,
                'revenue_target' => $row->revenue_target === null ? null : (float) $row->revenue_target,
                'log_revenue' => $row->log_revenue === null ? null : (float) $row->log_revenue,
                'temp_celsius' => $row->temp_celsius === null ? null : (float) $row->temp_celsius,
                'precipitation_mm' => $row->precipitation_mm === null ? null : (float) $row->precipitation_mm,
                'wind_speed' => $row->wind_speed === null ? null : (float) $row->wind_speed,
                'revenue_lag_1d' => $row->revenue_lag_1d === null ? null : (float) $row->revenue_lag_1d,
                'revenue_lag_7d' => $row->revenue_lag_7d === null ? null : (float) $row->revenue_lag_7d,
                'revenue_lag_365d' => $row->revenue_lag_365d === null ? null : (float) $row->revenue_lag_365d,
                'revenue_rolling_7d_mean' => $row->revenue_rolling_7d_mean === null ? null : (float) $row->revenue_rolling_7d_mean,
                'revenue_rolling_30d_mean' => $row->revenue_rolling_30d_mean === null ? null : (float) $row->revenue_rolling_30d_mean,
            ]);

        return response()->json(['data' => $rows->values()->all()]);
    }
}