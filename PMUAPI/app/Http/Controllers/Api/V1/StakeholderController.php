<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StakeholderResource;
use App\Models\Stakeholder;
use App\Models\StakeholderType;
use Illuminate\Http\Request;

class StakeholderController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $query = Stakeholder::with('stakeholderType');

        if ($stakeholder_type_id = request('stakeholder_type_id')) {
            $query->where('stakeholder_type_id', $stakeholder_type_id);
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('official_receipt', 'like', "%{$search}%");
            });
        }

        if (request()->has('page')) {
            return StakeholderResource::collection($query->paginate(request('per_page', 10)));
        }

        return StakeholderResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'official_receipt' => 'required|string|size:7|regex:/^\d{7}$/|unique:stakeholders,official_receipt',
            'stakeholder_type_id' => 'nullable|exists:stakeholder_types,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $stakeholder = Stakeholder::create($data);

        $this->logAudit('create', 'stakeholders', $stakeholder->id, null, $this->modelToArray($stakeholder, ['name', 'official_receipt', 'stakeholder_type_id', 'status']));

        return new StakeholderResource($stakeholder);
    }

    public function show(Stakeholder $stakeholder)
    {
        return new StakeholderResource($stakeholder->load('stakeholderType'));
    }

    public function update(Request $request, Stakeholder $stakeholder)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string',
            'official_receipt' => 'sometimes|required|string|size:7|regex:/^\d{7}$/|unique:stakeholders,official_receipt,'.$stakeholder->id,
            'stakeholder_type_id' => 'nullable|exists:stakeholder_types,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $oldValues = $this->modelToArray($stakeholder, ['name', 'official_receipt', 'stakeholder_type_id', 'status']);

        $stakeholder->update($data);

        $this->logAudit('update', 'stakeholders', $stakeholder->id, $oldValues, $this->modelToArray($stakeholder, ['name', 'official_receipt', 'stakeholder_type_id', 'status']));

        return new StakeholderResource($stakeholder->load('stakeholderType'));
    }

    public function destroy(Stakeholder $stakeholder)
    {
        $this->logAudit('delete', 'stakeholders', $stakeholder->id, $this->modelToArray($stakeholder, ['name', 'official_receipt', 'stakeholder_type_id', 'status']), null);

        $stakeholder->delete();

        return response()->noContent();
    }
}
