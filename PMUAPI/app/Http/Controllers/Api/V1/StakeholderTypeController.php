<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StakeholderTypeResource;
use App\Models\StakeholderType;
use Illuminate\Http\Request;

class StakeholderTypeController extends Controller
{
    public function index()
    {
        return StakeholderTypeResource::collection(StakeholderType::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:stakeholder_types,name',
            'description' => 'nullable|string',
        ]);

        return new StakeholderTypeResource(StakeholderType::create($data));
    }

    public function show(StakeholderType $stakeholderType)
    {
        return new StakeholderTypeResource($stakeholderType);
    }

    public function update(Request $request, StakeholderType $stakeholderType)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|unique:stakeholder_types,name,' . $stakeholderType->id,
            'description' => 'nullable|string',
        ]);

        $stakeholderType->update($data);

        return new StakeholderTypeResource($stakeholderType);
    }

    public function destroy(StakeholderType $stakeholderType)
    {
        $stakeholderType->delete();

        return response()->noContent();
    }
}
