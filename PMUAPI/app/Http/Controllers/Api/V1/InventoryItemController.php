<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryItemController extends Controller
{
    public function index()
    {
        $query = InventoryItem::query();

        if ($category = request('category')) {
            $query->where('category', $category);
        }

        return InventoryItemResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_name' => 'required|string',
            'category' => 'required|string',
            'category_type' => 'required|in:equipment,materials,supplies',
            'quantity' => 'nullable|integer|min:0',
            'unit' => 'nullable|string',
            'status' => 'nullable|in:available,low_stock,damaged',
        ]);

        return new InventoryItemResource(InventoryItem::create($data));
    }

    public function show(InventoryItem $item)
    {
        return new InventoryItemResource($item);
    }

    public function update(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'item_name' => 'sometimes|required|string',
            'category' => 'sometimes|required|string',
            'category_type' => 'sometimes|required|in:equipment,materials,supplies',
            'quantity' => 'nullable|integer|min:0',
            'unit' => 'nullable|string',
            'status' => 'nullable|in:available,low_stock,damaged',
        ]);

        $item->update($data);

        return new InventoryItemResource($item);
    }

    public function destroy(InventoryItem $item)
    {
        $item->delete();

        return response()->noContent();
    }

    public function logs(InventoryItem $item)
    {
        return response()->json(
            $item->logs()->with('user')->latest()->get()
        );
    }

    public function allLogs()
    {
        return response()->json(
            \App\Models\InventoryLog::with(['item', 'user'])->latest()->get()
        );
    }

    public function addStock(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);

        $item->increment('quantity', $data['quantity']);
        $item->update(['status' => $item->quantity > 0 ? 'available' : $item->status]);

        InventoryLog::create([
            'inventory_item_id' => $item->id,
            'action' => 'add',
            'quantity_changed' => $data['quantity'],
            'user_id' => Auth::id(),
        ]);

        return new InventoryItemResource($item);
    }

    public function deductStock(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);

        $item->decrement('quantity', $data['quantity']);

        $status = $item->quantity <= 0
            ? 'damaged'
            : ($item->quantity < 10 ? 'low_stock' : 'available');
        $item->update(['status' => $status]);

        InventoryLog::create([
            'inventory_item_id' => $item->id,
            'action' => 'deduct',
            'quantity_changed' => -$data['quantity'],
            'user_id' => Auth::id(),
        ]);

        return new InventoryItemResource($item);
    }
}
