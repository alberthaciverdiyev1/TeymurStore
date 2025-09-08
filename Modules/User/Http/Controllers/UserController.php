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
    function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function changeEmail(Request $request): JsonResponse
    {
        return $this->service->changeEmail($request);
    }
}
