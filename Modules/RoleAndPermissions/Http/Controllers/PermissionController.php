<?php

namespace Modules\RoleAndPermissions\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RoleAndPermissions\Services\PermissionService;
use Modules\User\Http\Entities\User;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;

        $this->middleware('permission:view roles-and-permissions')->only('getAll');
        $this->middleware('permission:add permission')->only('store');
        $this->middleware('permission:details permission')->only('show');
        $this->middleware('permission:update permission')->only('update');
        $this->middleware('permission:delete permission')->only('delete');
    }

    public function getAll()
    {
        return $this->permissionService->allPermissions();
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        return $this->permissionService->createPermission($request->name);
    }

    public function show($id)
    {
        return $this->permissionService->getPermission($id);
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate(['name' => 'required|string|max:255']);
        return $this->permissionService->updatePermission($permission, $request->name);
    }

    public function delete(Permission $permission)
    {
        return $this->permissionService->deletePermission($permission);
    }

}
