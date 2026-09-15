<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\FeeType;
use App\Models\RevenueHistory;
use App\Models\Transaction;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $query = Transaction::with(['stakeholder', 'items.feeType', 'recordedBy'])->latest();

        if (request()->has('page')) {
            return TransactionResource::collection($query->paginate(request('per_page', 10)));
        }

        return TransactionResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'stakeholder_id' => 'nullable|exists:stakeholders,id',
            'transaction_date' => 'required|date',
            'status' => 'nullable|in:pending,completed,cancelled',
            'remarks' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.fee_type_id' => 'required_with:items|exists:fee_types,id',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        $items = $data['items'] ?? [];
        $total = 0;

        $transaction = Transaction::create([
            'stakeholder_id' => $data['stakeholder_id'] ?? null,
            'transaction_date' => $data['transaction_date'],
            'status' => $data['status'] ?? 'completed',
            'remarks' => $data['remarks'] ?? null,
            'total_amount' => 0,
            'recorded_by' => Auth::id(),
        ]);

        foreach ($items as $item) {
            $feeType = FeeType::find($item['fee_type_id']);
            $baseRate = $feeType->base_rate ?? 0;
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) $baseRate;
            $subtotal = $unitPrice * $quantity;

            $transaction->items()->create([
                'fee_type_id' => $item['fee_type_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ]);

            $total += $subtotal;
        }

        $transaction->update(['total_amount' => $total]);

        $this->updateRevenueHistory($data['transaction_date'], $total);

        $weatherService = app(WeatherService::class);
        $weatherService->fetchForDate($data['transaction_date']);

        $this->logAudit('create', 'transactions', $transaction->id, null, $this->modelToArray($transaction, ['stakeholder_id', 'transaction_date', 'status', 'remarks', 'total_amount', 'recorded_by']));

        return new TransactionResource(
            $transaction->load(['stakeholder', 'items.feeType', 'recordedBy'])
        );
    }

    protected function updateRevenueHistory($date, $amount)
    {
        $revenueDate = is_string($date) ? $date : $date->toDateString();

        $existing = RevenueHistory::where('revenue_date', $revenueDate)->first();

        if ($existing) {
            $existing->update([
                'total_revenue' => $existing->total_revenue + $amount,
                'transaction_count' => $existing->transaction_count + 1,
            ]);
        } else {
            RevenueHistory::create([
                'revenue_date' => $revenueDate,
                'total_revenue' => $amount,
                'transaction_count' => 1,
            ]);
        }
    }

    public function show(Transaction $transaction)
    {
        return new TransactionResource(
            $transaction->load(['stakeholder', 'items.feeType', 'recordedBy'])
        );
    }

    public function update(Request $request, Transaction $transaction)
    {
        $oldTotal = (float) $transaction->total_amount;
        $oldDate = $transaction->transaction_date->toDateString();

        $oldValues = $this->modelToArray($transaction, ['stakeholder_id', 'transaction_date', 'status', 'remarks', 'total_amount', 'recorded_by']);

        $data = $request->validate([
            'stakeholder_id' => 'nullable|exists:stakeholders,id',
            'transaction_date' => 'sometimes|required|date',
            'status' => 'nullable|in:pending,completed,cancelled',
            'remarks' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.fee_type_id' => 'required_with:items|exists:fee_types,id',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        $transaction->update([
            'stakeholder_id' => $data['stakeholder_id'] ?? $transaction->stakeholder_id,
            'transaction_date' => $data['transaction_date'] ?? $transaction->transaction_date,
            'status' => $data['status'] ?? $transaction->status,
            'remarks' => $data['remarks'] ?? $transaction->remarks,
        ]);

        if (array_key_exists('items', $data)) {
            $transaction->items()->delete();

            $items = $data['items'] ?? [];
            $total = 0;

            foreach ($items as $item) {
                $feeType = FeeType::find($item['fee_type_id']);
                $baseRate = $feeType->base_rate ?? 0;
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $unitPrice = (float) $baseRate;
                $subtotal = $unitPrice * $quantity;

                $transaction->items()->create([
                    'fee_type_id' => $item['fee_type_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $transaction->update(['total_amount' => $total]);
        }

        $newTotal = (float) $transaction->total_amount;
        $newDate = $transaction->transaction_date->toDateString();
        $newStatus = $data['status'] ?? $transaction->status;

        if ($oldDate !== $newDate) {
            $this->adjustRevenueHistory($oldDate, -$oldTotal);
            $this->adjustRevenueHistory($newDate, $newTotal);

            $weatherService = app(WeatherService::class);
            $weatherService->fetchForDate($newDate);
        } elseif ($oldTotal != $newTotal) {
            $this->adjustRevenueHistory($newDate, $newTotal - $oldTotal);
        }

        if ($oldValues['status'] !== $newStatus) {
            $this->syncRevenueOnStatusChange($transaction, $oldValues['status'], $newStatus);
        }

        $this->logAudit('update', 'transactions', $transaction->id, $oldValues, $this->modelToArray($transaction, ['stakeholder_id', 'transaction_date', 'status', 'remarks', 'total_amount', 'recorded_by']));

        return new TransactionResource(
            $transaction->load(['stakeholder', 'items.feeType', 'recordedBy'])
        );
    }

    protected function syncRevenueOnStatusChange(Transaction $transaction, string $oldStatus, string $newStatus)
    {
        $date = $transaction->transaction_date->toDateString();
        $amount = (float) $transaction->total_amount;

        if ($oldStatus === 'completed') {
            $this->adjustRevenueHistory($date, -$amount);
        }

        if ($newStatus === 'completed') {
            $this->updateRevenueHistory($date, $amount);
        }

        $this->syncTransactionRevenue($transaction);
        $this->regenerateFeaturesForDate($date);
    }

    protected function syncTransactionRevenue(Transaction $transaction)
    {
        $reportDate = $transaction->transaction_date->toDateString();
        $completedTotal = Transaction::whereDate('transaction_date', $reportDate)
            ->where('status', 'completed')
            ->sum('total_amount');

        $weather = $this->getWeatherForDate($reportDate);

        if ($completedTotal > 0) {
            \App\Models\TransactionRevenue::updateOrInsert(
                ['report_date' => $reportDate],
                [
                    'revenue_target' => $completedTotal,
                    'log_revenue' => log($completedTotal + 1),
                    'temp_celsius' => $weather['temperature'] ?? null,
                    'precipitation_mm' => $weather['rainfall'] ?? null,
                    'wind_speed' => $weather['wind'] ?? null,
                    'year_num' => (int) date('Y', strtotime($reportDate)),
                    'month_num' => (int) date('n', strtotime($reportDate)),
                    'day_num' => (int) date('j', strtotime($reportDate)),
                    'day_of_week' => (int) date('N', strtotime($reportDate)),
                    'quarter_num' => (int) ceil(date('n', strtotime($reportDate)) / 3),
                    'is_weekend' => in_array((int) date('N', strtotime($reportDate)), [6, 7]),
                    'is_month_start' => date('j', strtotime($reportDate)) === 1,
                    'is_month_end' => date('j', strtotime($reportDate)) === date('t', strtotime($reportDate)),
                ]
            );
        }
    }

    protected function getWeatherForDate(string $date): array
    {
        $weather = \App\Models\WeatherData::where('weather_date', $date)->first();
        if ($weather) {
            return [
                'temperature' => $weather->temperature,
                'rainfall' => $weather->rainfall_mm,
                'wind' => $weather->wind_speed,
            ];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get(
                'https://archive-api.open-meteo.com/v1/archive',
                [
                    'latitude' => 13.5049,
                    'longitude' => 123.0434,
                    'start_date' => $date,
                    'end_date' => $date,
                    'daily' => 'temperature_2m_mean,precipitation_sum,wind_speed_10m_max',
                    'temperature_unit' => 'celsius',
                    'wind_speed_unit' => 'kmh',
                    'precipitation_unit' => 'mm',
                    'timezone' => 'Asia/Manila',
                ]
            );

            if ($response->successful()) {
                $daily = $response->json('daily');
                if (!empty($daily['time'][0])) {
                    return [
                        'temperature' => isset($daily['temperature_2m_mean'][0]) ? round((float) $daily['temperature_2m_mean'][0], 2) : null,
                        'rainfall' => isset($daily['precipitation_sum'][0]) ? round((float) $daily['precipitation_sum'][0], 2) : null,
                        'wind' => isset($daily['wind_speed_10m_max'][0]) ? round((float) $daily['wind_speed_10m_max'][0], 2) : null,
                    ];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Open-Meteo fetch failed', [
                'date' => $date,
                'message' => $e->getMessage(),
            ]);
        }

        return [];
    }

    protected function regenerateFeaturesForDate(string $reportDate)
    {
        $startDate = \Carbon\Carbon::parse($reportDate)->subDays(365)->toDateString();
        $endDate = \Carbon\Carbon::parse($reportDate)->addDays(30)->toDateString();

        $dates = \App\Models\TransactionRevenue::whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('report_date')
            ->get(['report_date']);

        $revenues = $dates->mapWithKeys(fn ($d) => [
            $d->report_date => \App\Models\TransactionRevenue::where('report_date', $d->report_date)->value('revenue_target') ?? 0,
        ]);

        $dates->each(function ($d) use ($revenues) {
            $date = $d->report_date;
            $lag1 = $revenues[$date] ?? null;
            $lag7 = $revenues[\Carbon\Carbon::parse($date)->subDays(7)->toDateString()] ?? null;
            $lag365 = $revenues[\Carbon\Carbon::parse($date)->subDays(365)->toDateString()] ?? null;
            $roll7 = null;
            $roll30 = null;

            $keys = array_keys($revenues->toArray());
            $idx = array_search($date, $keys);
            if ($idx !== false) {
                $slice7 = array_slice($revenues->toArray(), max(0, $idx - 6), 7, true);
                $slice30 = array_slice($revenues->toArray(), max(0, $idx - 29), 30, true);
                $roll7 = count($slice7) > 0 ? array_sum($slice7) / count($slice7) : null;
                $roll30 = count($slice30) > 0 ? array_sum($slice30) / count($slice30) : null;
            }

            \App\Models\TransactionRevenueFeature::updateOrInsert(
                ['report_date' => $date],
                [
                    'revenue_lag_1d' => $lag1,
                    'revenue_lag_7d' => $lag7,
                    'revenue_lag_365d' => $lag365,
                    'revenue_rolling_7d_mean' => $roll7,
                    'revenue_rolling_30d_mean' => $roll30,
                ]
            );
        });
    }

    protected function adjustRevenueHistory($date, $amountDelta)
    {
        $revenueDate = is_string($date) ? $date : $date->toDateString();

        $existing = RevenueHistory::where('revenue_date', $revenueDate)->first();

        if ($existing) {
            $existing->update([
                'total_revenue' => max(0, $existing->total_revenue + $amountDelta),
                'transaction_count' => max(0, $existing->transaction_count + ($amountDelta > 0 ? 1 : -1)),
            ]);
        } elseif ($amountDelta > 0) {
            RevenueHistory::create([
                'revenue_date' => $revenueDate,
                'total_revenue' => $amountDelta,
                'transaction_count' => 1,
            ]);
        }
    }

    public function destroy(Transaction $transaction)
    {
        $date = $transaction->transaction_date->toDateString();
        $amount = $transaction->total_amount;

        $this->logAudit('delete', 'transactions', $transaction->id, $this->modelToArray($transaction, ['stakeholder_id', 'transaction_date', 'status', 'remarks', 'total_amount', 'recorded_by']), null);

        $transaction->delete();

        $this->adjustRevenueHistory($date, -$amount);

        return response()->noContent();
    }
}
