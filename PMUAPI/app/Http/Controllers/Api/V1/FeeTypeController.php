<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeeTypeResource;
use App\Models\FeeType;
use Illuminate\Http\Request;

class FeeTypeController extends Controller
{
    public function index()
    {
        return FeeTypeResource::collection(FeeType::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fee_name' => 'required|string',
            'base_rate' => 'required|numeric|min:0',
            'unit' => 'nullable|string',
        ]);

        return new FeeTypeResource(FeeType::create($data));
    }

    public function show(FeeType $feeType)
    {
        return new FeeTypeResource($feeType);
    }

    public function update(Request $request, FeeType $feeType)
    {
        $data = $request->validate([
            'fee_name' => 'sometimes|required|string',
            'base_rate' => 'sometimes|required|numeric|min:0',
            'unit' => 'nullable|string',
        ]);

        $feeType->update($data);

        return new FeeTypeResource($feeType);
    }

    public function destroy(FeeType $feeType)
    {
        $feeType->delete();

        return response()->noContent();
    }
}
