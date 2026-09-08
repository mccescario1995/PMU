<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $query = User::with('roles', 'permissions');

        if (request()->has('page')) {
            return UserResource::collection($query->paginate(request('per_page', 10)));
        }

        return UserResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'status' => 'nullable|in:active,inactive',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        $this->logAudit('create', 'users', $user->id, null, $this->modelToArray($user, ['name', 'email', 'status']));

        if (! empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return new UserResource($user->load('roles', 'permissions'));
    }

    public function show(User $user)
    {
        return new UserResource($user->load('roles', 'permissions'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string',
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'status' => 'nullable|in:active,inactive',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $oldValues = $this->modelToArray($user, ['name', 'email', 'status']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        $this->logAudit('update', 'users', $user->id, $oldValues, $this->modelToArray($user, ['name', 'email', 'status']));

        return new UserResource($user->load('roles', 'permissions'));
    }

    public function destroy(User $user)
    {
        $this->logAudit('delete', 'users', $user->id, $this->modelToArray($user, ['name', 'email', 'status']), null);

        $user->delete();

        return response()->noContent();
    }
}
