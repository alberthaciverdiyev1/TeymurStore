<?php

namespace Modules\RoleAndPermissions\Services;

use Exception;
use Modules\User\Http\Entities\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleService
{
    protected string $guard = 'sanctum';

    public function getAll()
    {
        $roles = Role::all();
        return responseHelper('Roles retrieved successfully.', 200, $roles);
    }

    public function add(string $name)
    {
        $role = Role::create([
            'name' => $name,
            'guard_name' => $this->guard,
        ]);
        return responseHelper('Role created successfully.', 201, $role);
    }

    public function details(string|int $roleId)
    {
        $role = Role::with('permissions')->find($roleId);
        if (!$role) {
            return responseHelper('Role not found.', 404);
        }

        return responseHelper('Role details retrieved successfully.', 200, $role);
    }


    public function update(Role $role, string $name)
    {
        $role->name = $name;
        $role->save();
        return responseHelper('Role updated successfully.', 200, $role);
    }

    public function delete(int $roleId): array
    {
        $role = Role::find($roleId);

        if (!$role) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'Role not found.',
                'data' => null
            ];
        }

        try {
            $role->permissions()->detach();

            $role->delete();

            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'Role deleted successfully.',
                'data' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to delete role: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function givePermission(Role $role, string|Permission $permission)
    {
        if (is_string($permission)) {
            $permission = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $this->guard,
            ]);
        }

        $role->givePermissionTo($permission);
        return responseHelper('Permission assigned to role successfully.', 200, $role->permissions);
    }

    public function revokePermission(Role $role, string|Permission $permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where([
                'name' => $permission,
                'guard_name' => $this->guard
            ])->first();
        }

        if ($permission) {
            $role->revokePermissionTo($permission);
        }

        return responseHelper('Permission revoked from role successfully.', 200, $role->permissions);
    }

    public function giveRoleToUser(User $user, string|Role $role)
    {
        if (is_string($role)) {
            $role = Role::where([
                'name' => $role,
                'guard_name' => $this->guard
            ])->first();
        }

        if ($role) {
            $user->assignRole($role);
        }

        return responseHelper('Role assigned to user successfully.', 200, $user->roles);
    }

    public function revokeRoleFromUser(User $user, string|Role $role)
    {
        if (is_string($role)) {
            $role = Role::where([
                'name' => $role,
                'guard_name' => $this->guard
            ])->first();
        }

        if ($role) {
            $user->removeRole($role);
        }

        return responseHelper('Role removed from user successfully.', 200, $user->roles);
    }

    public function getUsersWithRole()
    {
        return User::with('roles')->get();
    }

}
