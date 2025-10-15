<?php

namespace Modules\RoleAndPermissions\Services;

use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionService
{
    protected string $guard = 'sanctum';

    public function allPermissions()
    {
        $permissions = Permission::all();
        return responseHelper('Permissions retrieved successfully.', 200, $permissions);
    }

    public function createPermission(string $name)
    {
        $permission = Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => $this->guard,
        ]);
        return responseHelper('Permission created successfully.', 201, $permission);
    }

    public function getPermission(string|int $permissionId)
    {
        $permission = Permission::find($permissionId);
        if (!$permission) {
            return responseHelper('Permission not found.', 404);
        }
        return responseHelper('Permission details retrieved successfully.', 200, $permission);
    }

    public function updatePermission(Permission|string $permission, string $name)
    {
        if (is_string($permission)) {
            $permission = Permission::where([
                'name' => $permission,
                'guard_name' => $this->guard,
            ])->first();
        }

        if (!$permission) {
            return responseHelper('Permission not found.', 404);
        }

        $permission->name = $name;
        $permission->save();

        return responseHelper('Permission updated successfully.', 200, $permission);
    }

    public function deletePermission(Permission|string $permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where([
                'name' => $permission,
                'guard_name' => $this->guard,
            ])->first();
        }

        if ($permission) {
            $permission->delete();
        }

        return responseHelper('Permission deleted successfully.', 200);
    }
}
