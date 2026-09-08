<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StakeholderTypeResource;
use App\Models\StakeholderType;
use Illuminate\Http\Request;

class StakeholderTypeController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $query = StakeholderType::query();

        if (request()->has('page')) {
            return StakeholderTypeResource::collection($query->paginate(request('per_page', 10)));
        }

        return StakeholderTypeResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:stakeholder_types,name',
            'description' => 'nullable|string',
        ]);

        $stakeholderType = StakeholderType::create($data);

        $this->logAudit('create', 'stakeholder_types', $stakeholderType->id, null, $this->modelToArray($stakeholderType, ['name', 'description']));

        return new StakeholderTypeResource($stakeholderType);
    }

    public function show(StakeholderType $stakeholderType)
    {
        return new StakeholderTypeResource($stakeholderType);
    }

    public function update(Request $request, StakeholderType $stakeholderType)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|unique:stakeholder_types,name,'.$stakeholderType->id,
            'description' => 'nullable|string',
        ]);

        $oldValues = $this->modelToArray($stakeholderType, ['name', 'description']);

        $stakeholderType->update($data);

        $this->logAudit('update', 'stakeholder_types', $stakeholderType->id, $oldValues, $this->modelToArray($stakeholderType, ['name', 'description']));

        return new StakeholderTypeResource($stakeholderType);
    }

    public function destroy(StakeholderType $stakeholderType)
    {
        $this->logAudit('delete', 'stakeholder_types', $stakeholderType->id, $this->modelToArray($stakeholderType, ['name', 'description']), null);

        $stakeholderType->delete();

        return response()->noContent();
    }
}
