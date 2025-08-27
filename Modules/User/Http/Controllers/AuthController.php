<?php

namespace Modules\User\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Services\AuthService;
use Nwidart\Modules\Facades\Module;

class AuthController extends Controller
{
    private AuthService $service;

    function __construct(AuthService $service)
    {
        $this->service = $service;

        //        if (Module::find('Roles')->isEnabled()) {
//            $this->middleware('permission:view users')->only('index');
//            $this->middleware('permission:create user')->only('create');
//            $this->middleware('permission:store user')->only('store');
//            $this->middleware('permission:edit user')->only('edit');
//            $this->middleware('permission:update user')->only('update');
//            $this->middleware('permission:destroy user')->only('destroy');
//        }
    }

    public function register(Request $request)
    {
        return $this->service->register($request);
    }

    public function sendOtp(Request $request)
    {
        return $this->service->sendOtp($request);
    }

    public function login(Request $request)
    {
        return $this->service->login($request);
    }

    public function logout(Request $request)
    {
        return $this->service->logout();
    }

    public function resetPassword(Request $request)
    {
        $this->service->resetPassword($request);
    }
}
