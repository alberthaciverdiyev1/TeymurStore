<?php

namespace Modules\Payment\Service;

use App\Enums\OrderStatus as OrderStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Balance\Services\BalanceService;
use Modules\Order\Http\Entities\Order;
use Modules\Order\Http\Entities\OrderStatus;
use Modules\Order\Services\OrderService;

class PaymentService
{
    private BalanceService $balanceService;
    private OrderService $orderService;

    function __construct(BalanceService $balanceService, OrderService $orderService)
    {
        $this->balanceService = $balanceService;
        $this->orderService = $orderService;
    }

    public function success(Request $request)
    {
        $transactionId = $request->query('transaction_id');
        // $transactionId = 'EAF4DDC6-5326-4EC9-A833-20EB2C87DC5E';

        $order = Order::where('transaction_id', $transactionId)->first();
        if (!$order) {
            return responseHelper('Order not found', 403);
        }
        $userId = $order->user_id;

        $response = EPointService::checkPayment(
            env('EPOINT_PRIVATE_KEY'),
            env('EPOINT_PUBLIC_KEY'),
            $order->id
        );

        $success = isset($response->code) && (string)$response->code === '000';
        $amountPaid = $response->amount ?? 0;

        if ($success) {
            if (!$order->paid_at) {
                $order->update([
                    'paid_at' => now(),
                ]);

                handleTransaction(fn() => OrderStatus::create([
                    'order_id' => $order->id,
                    'status' => OrderStatusEnum::PLACED,
                ]));


                $this->balanceService->callbackDeposit($userId, $amountPaid, "Added amount to balance for order: $order->id");
                $this->balanceService->withdraw($userId, $amountPaid, "Removed amount to balance for order: $order->id");
            }


            return $this->orderService->getReceipt($order->id, $userId, 'Payment successful');
        }

        handleTransaction(fn() => OrderStatus::create([
            'order_id' => $order->id,
            'status' => OrderStatusEnum::FAILED,
        ]));

        return responseHelper('Payment failed', 403, [
            'order_id' => $order->id,
            'status' => OrderStatusEnum::FAILED,
            'response' => $response,
        ]);
    }

    public function error(Request $request)
    {
        $transactionId = $request->query('transaction_id');

        $order = Order::where('transaction_id', $transactionId)->first();

        if (!$order) {
            return responseHelper('Order not found', 404);
        }

        handleTransaction(fn() => OrderStatus::create([
            'order_id' => $order->id,
            'status' => OrderStatusEnum::FAILED,
        ]));

        return responseHelper('Payment failed', 500, [
            'order_id' => $order->id,
            'status' => OrderStatusEnum::FAILED->label()
        ]);
    }

    public function result(Request $request)
    {
        Log::error('result');
        $transactionId = $request->query('transaction_id');
        //   $transactionId = 'EAF4DDC6-5326-4EC9-A833-20EB2C87DC5E';

        $order = Order::where('transaction_id', $transactionId)->first();
        if (!$order) {
            return responseHelper('Order not found', 403);
        }

        $response = EPointService::checkPayment(
            env('EPOINT_PRIVATE_KEY'),
            env('EPOINT_PUBLIC_KEY'),
            $order->id
        );

        $success = isset($response->code) && (string)$response->code === '000';
        $amountPaid = $response->amount ?? 0;

        if ($success) {
            if (!$order->paid_at) {
                $order->update([
                    'paid_at' => now(),
                ]);

                handleTransaction(fn() => OrderStatus::create([
                    'order_id' => $order->id,
                    'status' => OrderStatusEnum::PLACED,
                ]));

                $userId = $order->user_id;

                $this->balanceService->callbackDeposit($userId, $amountPaid, "Added amount to balance for order: $order->id");
                $this->balanceService->withdraw($userId, $amountPaid, "Removed amount to balance for order: $order->id");
            }

            return responseHelper('Payment successful', 200, [
                'order_id' => $order->id,
                'status' => OrderStatusEnum::PLACED,
                'amount_paid' => $amountPaid,
            ]);
        }

        handleTransaction(fn() => OrderStatus::create([
            'order_id' => $order->id,
            'status' => OrderStatusEnum::FAILED,
        ]));

        return responseHelper('Payment failed', 403, [
            'order_id' => $order->id,
            'status' => OrderStatusEnum::FAILED->label(),
            'response' => $response,
        ]);
    }
}
