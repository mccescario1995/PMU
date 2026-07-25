<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\FeeType;
use App\Models\RevenueHistory;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index()
    {
        return TransactionResource::collection(
            Transaction::with(['stakeholder', 'items.feeType', 'recordedBy'])
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'stakeholder_id' => 'nullable|exists:stakeholders,id',
            'transaction_date' => 'required|date',
            'status' => 'nullable|in:pending,completed,cancelled',
            'remarks' => 'nullable|string',
            'total_amount' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.fee_type_id' => 'required_with:items|exists:fee_types,id',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'nullable|numeric|min:0',
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
            $quantity = $item['quantity'] ?? 1;
            $unitPrice = $item['unit_price'] ?? $baseRate;
            $subtotal = $item['subtotal'] ?? ($unitPrice * $quantity);

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
        $oldTotal = $transaction->total_amount;
        $oldDate = $transaction->transaction_date->toDateString();

        $data = $request->validate([
            'stakeholder_id' => 'nullable|exists:stakeholders,id',
            'transaction_date' => 'sometimes|required|date',
            'status' => 'nullable|in:pending,completed,cancelled',
            'remarks' => 'nullable|string',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        $transaction->update($data);

        // Update revenue history if date or amount changed
        $newTotal = $transaction->total_amount;
        $newDate = $transaction->transaction_date->toDateString();

        if ($oldDate !== $newDate) {
            // Remove from old date
            $this->adjustRevenueHistory($oldDate, -$oldTotal);
            // Add to new date
            $this->adjustRevenueHistory($newDate, $newTotal);
        } elseif ($oldTotal != $newTotal) {
            $this->adjustRevenueHistory($newDate, $newTotal - $oldTotal);
        }

        return new TransactionResource(
            $transaction->load(['stakeholder', 'items.feeType', 'recordedBy'])
        );
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

        $transaction->delete();

        $this->adjustRevenueHistory($date, -$amount);

        return response()->noContent();
    }
}
