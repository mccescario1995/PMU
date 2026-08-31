<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->pluck('name');

        $groups = [];
        foreach ($permissions as $name) {
            $parts = explode(' ', $name, 2);
            $action = $parts[0];
            $resource = $parts[1] ?? $name;

            $groups[$resource][] = [
                'name' => $name,
                'action' => $action,
            ];
        }

        return response()->json(
            collect($groups)
                ->map(fn ($items, $resource) => [
                    'resource' => $resource,
                    'permissions' => $items,
                ])
                ->values()
                ->all()
        );
    }
}
