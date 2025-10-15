<?php

namespace Modules\Payment\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nwidart\Modules\Facades\Module;

class PaymentController extends Controller
{

    public function __construct()
    {
//        if (Module::find('Roles')->isEnabled()) {
//            $this->middleware('permission:view payments')->only('index');
//            $this->middleware('permission:create payment')->only('create');
//            $this->middleware('permission:store payment')->only('store');
//            $this->middleware('permission:edit payment')->only('edit');
//            $this->middleware('permission:update payment')->only('update');
//            $this->middleware('permission:destroy payment')->only('destroy');
//        }
    }


    public function success()
    {
        return response()->json(['message' => 'Payment successful'], 200);

    }

    public function error()
    {
        return response()->json(['message' => 'Payment failed'], 500);
    }

    public function result()
    {
        return response()->json(['message' => 'Payment result'], 200);
    }
}
