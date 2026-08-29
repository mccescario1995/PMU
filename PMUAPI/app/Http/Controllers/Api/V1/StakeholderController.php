<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StakeholderResource;
use App\Models\Stakeholder;
use App\Models\StakeholderType;
use Illuminate\Http\Request;

class StakeholderController extends Controller
{
    public function index()
    {
        $query = Stakeholder::with('stakeholderType');

        if ($type = request('type')) {
            $query->where('type', $type);
        }

        return StakeholderResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:buyer,broker,renter',
            'stakeholder_type_id' => 'nullable|exists:stakeholder_types,id',
            'contact_no' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if (empty($data['stakeholder_type_id'])) {
            $typeName = ucfirst($data['type']);
            $type = StakeholderType::firstOrCreate(['name' => $typeName]);
            $data['stakeholder_type_id'] = $type->id;
        }

        return new StakeholderResource(Stakeholder::create($data));
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

        if (! empty($data['stakeholder_type_id'])) {
            $data['stakeholder_type_id'] = $data['stakeholder_type_id'];
        } elseif (! empty($data['type'])) {
            $typeName = ucfirst($data['type']);
            $type = StakeholderType::firstOrCreate(['name' => $typeName]);
            $data['stakeholder_type_id'] = $type->id;
        }

        $stakeholder->update($data);

        return new StakeholderResource($stakeholder->load('stakeholderType'));
    }

    public function destroy(Stakeholder $stakeholder)
    {
        $stakeholder->delete();

        return response()->noContent();
    }
}
