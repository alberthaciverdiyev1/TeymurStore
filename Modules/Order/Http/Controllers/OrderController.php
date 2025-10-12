<?php

namespace Modules\Order\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Order\Http\Requests\OrderAddRequest;
use Modules\Order\Http\Requests\OrderBuyOneAddRequest;
use Modules\Order\Http\Requests\OrderUpdateRequest;
use Modules\Order\Services\OrderService;
use Modules\User\Http\Requests\Address\AddressAddRequest;
use Modules\User\Http\Requests\Address\AddressUpdateRequest;
use Nwidart\Modules\Facades\Module;

class OrderController extends Controller
{

    private OrderService $service;

    public function __construct(OrderService $service)
    {
        $this->service = $service;
        //       if (Module::find('Roles')->isEnabled()) {
//            $this->middleware('permission:view orders')->only('index');
//            $this->middleware('permission:create order')->only('create');
//            $this->middleware('permission:store order')->only('store');
//            $this->middleware('permission:edit order')->only('edit');
//            $this->middleware('permission:update order')->only('update');
//            $this->middleware('permission:destroy order')->only('destroy');
//        }
    }

    public function getAll(Request $request): JsonResponse
    {
        return $this->service->getAll($request);
    }

    public function getAllAdmin(Request $request): JsonResponse
    {
        return $this->service->getAllAdmin($request);
    }

    public function details(int $id): JsonResponse
    {
        return $this->service->details($id);
    }

    public function orderFromBasket(OrderAddRequest $request)
    {
        return $this->service->orderFromBasket($request);
    }

    public function buyOne(OrderBuyOneAddRequest $request, $product_id): JsonResponse
    {
        return $this->service->buyOne($request, $product_id);
    }

    public function update(int $id, OrderUpdateRequest $request): JsonResponse
    {
        return $this->service->update($id, $request);
    }

    public function delete(int $id): JsonResponse
    {
        return $this->service->delete($id);
    }
    public function getReceipt(int $orderId): JsonResponse
    {
        return $this->service->getReceipt($orderId);
    }
    public function downloadReceipt(int $orderId)
    {
        return $this->service->downloadReceipt($orderId);
    }

    public function completedOrders(Request $request)
    {
        return $this->service->completedOrders($request);
    }
}
