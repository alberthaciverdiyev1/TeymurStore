<?php

namespace Modules\Balance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Balance\Services\BalanceService;
use Modules\Balance\Http\Requests\BalanceRequest;

class BalanceController extends Controller
{
    private BalanceService $service;

    public function __construct(BalanceService $service)
    {
        $this->service = $service;

//        if (Module::find('Roles')->isEnabled()) {
//            $this->middleware('permission:view balances')->only('getBalance', 'getBalanceHistory');
//            $this->middleware('permission:create balance')->only('deposit');
//            $this->middleware('permission:update balance')->only('withdraw');
//        }
    }


    public function deposit(BalanceRequest $request)
    {
        return $this->service->deposit($request);
    }


    public function withdraw(BalanceRequest $request)
    {
        return $this->service->withdraw($request);
    }


    public function getBalance(Request $request)
    {
        $userId = $request->user_id ?? null;
        return $this->service->getBalance($userId);
    }

    public function getBalanceHistory(Request $request)
    {
        $userId = $request->user_id ?? null;
        return $this->service->getBalanceHistory($userId);
    }
}
