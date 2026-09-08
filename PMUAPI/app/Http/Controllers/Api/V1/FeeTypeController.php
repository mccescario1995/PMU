<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeeTypeResource;
use App\Models\FeeType;
use Illuminate\Http\Request;

class FeeTypeController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $query = FeeType::query();

        if (request()->has('page')) {
            return FeeTypeResource::collection($query->paginate(request('per_page', 10)));
        }

        return FeeTypeResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fee_name' => 'required|string',
            'base_rate' => 'required|numeric|min:0',
            'unit' => 'nullable|string',
        ]);

        $feeType = FeeType::create($data);

        $this->logAudit('create', 'fee_types', $feeType->id, null, $this->modelToArray($feeType, ['fee_name', 'base_rate', 'unit']));

        return new FeeTypeResource($feeType);
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

        $oldValues = $this->modelToArray($feeType, ['fee_name', 'base_rate', 'unit']);

        $feeType->update($data);

        $this->logAudit('update', 'fee_types', $feeType->id, $oldValues, $this->modelToArray($feeType, ['fee_name', 'base_rate', 'unit']));

        return new FeeTypeResource($feeType);
    }

    public function destroy(FeeType $feeType)
    {
        $this->logAudit('delete', 'fee_types', $feeType->id, $this->modelToArray($feeType, ['fee_name', 'base_rate', 'unit']), null);

        $feeType->delete();

        return response()->noContent();
    }
}
