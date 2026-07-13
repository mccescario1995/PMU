<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StakeholderResource;
use App\Models\Stakeholder;
use Illuminate\Http\Request;

class StakeholderController extends Controller
{
    public function index()
    {
        $query = Stakeholder::query();

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
            'contact_no' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        return new StakeholderResource(Stakeholder::create($data));
    }

    public function show(Stakeholder $stakeholder)
    {
        return new StakeholderResource($stakeholder);
    }

    public function update(Request $request, Stakeholder $stakeholder)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:buyer,broker,renter',
            'contact_no' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $stakeholder->update($data);

        return new StakeholderResource($stakeholder);
    }

    public function destroy(Stakeholder $stakeholder)
    {
        $stakeholder->delete();

        return response()->noContent();
    }
}
