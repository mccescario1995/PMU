<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
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
        $total = $data['total_amount'] ?? collect($items)->sum('subtotal');

        $transaction = Transaction::create([
            'stakeholder_id' => $data['stakeholder_id'] ?? null,
            'transaction_date' => $data['transaction_date'],
            'status' => $data['status'] ?? 'completed',
            'remarks' => $data['remarks'] ?? null,
            'total_amount' => $total,
            'recorded_by' => Auth::id(),
        ]);

        foreach ($items as $item) {
            $transaction->items()->create([
                'fee_type_id' => $item['fee_type_id'],
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? 0,
                'subtotal' => $item['subtotal'] ?? 0,
            ]);
        }

        return new TransactionResource(
            $transaction->load(['stakeholder', 'items.feeType', 'recordedBy'])
        );
    }

    public function show(Transaction $transaction)
    {
        return new TransactionResource(
            $transaction->load(['stakeholder', 'items.feeType', 'recordedBy'])
        );
    }

    public function update(Request $request, Transaction $transaction)
    {
        $data = $request->validate([
            'stakeholder_id' => 'nullable|exists:stakeholders,id',
            'transaction_date' => 'sometimes|required|date',
            'status' => 'nullable|in:pending,completed,cancelled',
            'remarks' => 'nullable|string',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        $transaction->update($data);

        return new TransactionResource(
            $transaction->load(['stakeholder', 'items.feeType', 'recordedBy'])
        );
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return response()->noContent();
    }
}
