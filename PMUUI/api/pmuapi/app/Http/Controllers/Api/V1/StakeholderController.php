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

        if ($type = request('type')) {
            $query->where('type', $type);
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_no', 'like', "%{$search}%");
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
            'type' => 'sometimes|required|in:buyer,broker,renter',
            'stakeholder_type_id' => 'nullable|exists:stakeholder_types,id',
            'contact_no' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        // if (empty($data['stakeholder_type_id'])) {
        //     $typeName = ucfirst($data['type']);
        //     $type = StakeholderType::firstOrCreate(['name' => $typeName]);
        //     $data['stakeholder_type_id'] = $type->id;
        // }

        if (empty($data['stakeholder_type_id']) && ! empty($data['type'])) {
            $typeName = ucfirst($data['type']);
            $type = StakeholderType::firstOrCreate(['name' => $typeName]);
            $data['stakeholder_type_id'] = $type->getKey();
        }

        $stakeholder = Stakeholder::create($data);

        $this->logAudit('create', 'stakeholders', $stakeholder->id, null, $this->modelToArray($stakeholder, ['name', 'type', 'stakeholder_type_id', 'contact_no', 'email', 'address', 'status']));

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
            'type' => 'sometimes|required|in:buyer,broker,renter',
            'stakeholder_type_id' => 'nullable|exists:stakeholder_types,id',
            'contact_no' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $oldValues = $this->modelToArray($stakeholder, ['name', 'type', 'stakeholder_type_id', 'contact_no', 'email', 'address', 'status']);

        if (empty($data['stakeholder_type_id']) && ! empty($data['type'])) {
            $typeName = ucfirst($data['type']);
            $type = StakeholderType::firstOrCreate(['name' => $typeName]);
            $data['stakeholder_type_id'] = $type->getKey();
        }

        $stakeholder->update($data);

        $this->logAudit('update', 'stakeholders', $stakeholder->id, $oldValues, $this->modelToArray($stakeholder, ['name', 'type', 'stakeholder_type_id', 'contact_no', 'email', 'address', 'status']));

        return new StakeholderResource($stakeholder->load('stakeholderType'));
    }

    public function destroy(Stakeholder $stakeholder)
    {
        $this->logAudit('delete', 'stakeholders', $stakeholder->id, $this->modelToArray($stakeholder, ['name', 'type', 'stakeholder_type_id', 'contact_no', 'email', 'address', 'status']), null);

        $stakeholder->delete();

        return response()->noContent();
    }
}
