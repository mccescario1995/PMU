<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeeTypeResource;
use App\Http\Resources\StakeholderResource;
use App\Http\Resources\StakeholderTypeResource;
use App\Models\FeeType;
use App\Models\Stakeholder;
use App\Models\StakeholderType;
use Spatie\Permission\Models\Role;

class DropdownController extends Controller
{
    public function stakeholders()
    {
        $query = Stakeholder::query()->orderBy('name');

        if ($type = request('type')) {
            $query->where('type', $type);
        }

        if ($search = request('search')) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_no', 'like', "%{$search}%");
            });
        }

        return StakeholderResource::collection($query->get());
    }

    public function stakeholderTypes()
    {
        return StakeholderTypeResource::collection(
            StakeholderType::query()->orderBy('name')->get()
        );
    }

    public function feeTypes()
    {
        return FeeTypeResource::collection(
            FeeType::query()->orderBy('fee_name')->get()
        );
    }

    public function roles()
    {
        return response()->json(
            Role::query()->orderBy('name')->get()->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'created_at' => $role->created_at,
            ])->values()->all()
        );
    }
}
