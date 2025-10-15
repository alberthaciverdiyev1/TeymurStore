<?php

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Order\Http\Entities\Order;
use Modules\Order\Http\Entities\OrderStatus;
use App\Enums\OrderStatus as OrderStatusEnum;

class PaymentController extends Controller
{
    public function success(Request $request)
    {
        $transactionId = $request->query('transaction_id');

        $order = Order::where('transaction_id', $transactionId)->first();

        if (!$order) {
            return responseHelper('Order not found', 404);
        }

        if (!$order->paid_at) {
            $order->update(['paid_at' => now()]);

            handleTransaction(fn() => OrderStatus::create([
                'order_id' => $order->id,
                'status' => OrderStatusEnum::PLACED,
            ]));
        }

        return responseHelper('Payment successful', 200, [
            'order_id' => $order->id,
            'status' => OrderStatusEnum::PLACED
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
            'status' => OrderStatusEnum::FAILED
        ]);
    }

    public function result(Request $request)
    {
        try {
            $transactionId = $request->input('transaction_id');
            $paymentStatus = $request->input('status');

            $order = Order::where('transaction_id', $transactionId)->first();

            if (!$order) {
                return responseHelper('Order not found', 404);
            }

            if ($paymentStatus === 'success') {
                if (!$order->paid_at) {
                    $order->update(['paid_at' => now()]);
                }

                handleTransaction(fn() => OrderStatus::create([
                    'order_id' => $order->id,
                    'status' => OrderStatusEnum::PLACED,
                ]));

            } else {
                handleTransaction(fn() => OrderStatus::create([
                    'order_id' => $order->id,
                    'status' => OrderStatusEnum::FAILED,
                ]));
            }

            return responseHelper('Payment result recorded', 200);

        } catch (\Throwable $e) {
            Log::error('Payment webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return responseHelper('Error processing payment result', 500);
        }
    }
}
