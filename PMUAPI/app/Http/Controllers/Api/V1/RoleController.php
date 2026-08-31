<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $query = Role::with('permissions');

        if (request()->has('page')) {
            $roles = $query->paginate(request('per_page', 10));
            $roles->getCollection()->transform(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'created_at' => $role->created_at,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ]);

            return response()->json($roles);
        }

        return response()->json(
            $query->get()->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'created_at' => $role->created_at,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ])
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        $this->logAudit('create', 'roles', $role->id, null, $this->modelToArray($role, ['name', 'guard_name']));

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

        $oldValues = $this->modelToArray($role, ['name', 'guard_name']);

        $role->update(['name' => $data['name'] ?? $role->name]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        $this->logAudit('update', 'roles', $role->id, $oldValues, $this->modelToArray($role, ['name', 'guard_name']));

        return response()->json($role->load('permissions'));
    }

    public function destroy(Role $role)
    {
        $this->logAudit('delete', 'roles', $role->id, $this->modelToArray($role, ['name', 'guard_name']), null);

        $role->delete();

        return response()->noContent();
    }
}
