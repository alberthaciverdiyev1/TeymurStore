<?php

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Order\Http\Entities\Order;
use Modules\Order\Http\Entities\OrderStatus;
use App\Enums\OrderStatus as OrderStatusEnum;
use Modules\Payment\Service\PaymentService;

class PaymentController extends Controller
{
    private PaymentService $service;

    function __construct(PaymentService $service)
    {
        $this->service = $service;
    }
    public function success(Request $request)
    {
       return $this->service->success($request);
    }

    public function error(Request $request)
    {
      return $this->service->error($request);
    }

    public function result(Request $request)
    {
         return $this->service->result($request);
    }
}
