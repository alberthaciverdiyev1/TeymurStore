<?php

namespace Modules\RoleAndPermissions\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RoleAndPermissions\Services\RoleService;
use Modules\User\Http\Entities\User;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;

        $this->middleware('permission:view roles-and-permissions')->only('getAll');
        $this->middleware('permission:add role')->only('store');
        $this->middleware('permission:details role')->only('show');
        $this->middleware('permission:update role')->only('update');
        $this->middleware('permission:delete role')->only('delete');
        $this->middleware('permission:give-role-to-user')->only('assignRoleToUser');
        $this->middleware('permission:revoke-role-from-user')->only('revokeRoleFromUser');
    }

    public function getAll()
    {
        return $this->roleService->getAll();
    }

    public function add(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        return $this->roleService->add($request->name);
    }

    public function details($id)
    {
        return $this->roleService->details($id);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate(['name' => 'required|string|max:255']);
        return $this->roleService->update($role, $request->name);
    }

    public function delete(Role $role)
    {
        return $this->roleService->delete($role);
    }

    public function assignRoleToUser(Request $request, $userId)
    {
        $request->validate(['role' => 'required|string']);
        $user = User::findOrFail($userId);

        return $this->roleService->giveRoleToUser($user, $request->role);
    }

    public function revokeRoleFromUser(Request $request, $userId)
    {
        $request->validate(['role' => 'required|string']);
        $user = User::findOrFail($userId);

        return $this->roleService->revokeRoleFromUser($user, $request->role);
    }
    public function givePermission(Request $request, Role $role)
    {
        $request->validate(['permission' => 'required|string']);
        return $this->roleService->givePermission($role, $request->permission);
    }

    public function revokePermission(Request $request, Role $role)
    {
        $request->validate(['permission' => 'required|string']);
        return $this->roleService->revokePermission($role, $request->permission);
    }
}
