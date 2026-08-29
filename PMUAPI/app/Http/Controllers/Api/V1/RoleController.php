<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(Role::all(['id', 'name', 'guard_name', 'created_at']));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        if (! empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json($role, 201);
    }

    public function show(Role $role)
    {
        $role->load('permissions');

        return response()->json($role);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->update(['name' => $data['name'] ?? $role->name]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json($role->load('permissions'));
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return response()->noContent();
    }
}
