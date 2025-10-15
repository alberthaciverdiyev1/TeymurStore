<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Services\UserService;

/**
 * @property UserService $service
 */
class UserController extends Controller
{
    private UserService $service;

    function __construct(UserService $service)
    {
        $this->middleware('permission:view users')->only('getAll');
        $this->middleware('permission:details user')->only('details');
        $this->middleware('permission:update user')->only('changeEmail', 'changeName', 'changeSurname', 'changePhone');

        $this->service = $service;
    }

    public function changeEmail(Request $request): JsonResponse
    {
        return $this->service->changeEmail($request);
    }

    public function changeName(Request $request): JsonResponse
    {
        return $this->service->changeName($request);
    }

    public function changeSurname(Request $request): JsonResponse
    {
        return $this->service->changeSurname($request);
    }

    public function changePhone(Request $request): JsonResponse
    {
        return $this->service->changePhone($request);
    }

    public function getAll(): JsonResponse
    {
        return $this->service->getAll();
    }

    public function details(int $id = null): JsonResponse
    {
        return $this->service->details($id);
    }

    public function deleteMyAccount(): JsonResponse
    {
        return $this->service->deleteMyAccount();
    }
}
