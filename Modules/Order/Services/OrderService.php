<?php

namespace Modules\Order\Services;

use App\Enums\BalanceType;
use App\Interfaces\ICrudInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Balance\Services\BalanceService;
use Modules\Delivery\Services\DeliveryService;
use Modules\Order\Http\Entities\Order;
use Modules\Order\Http\Entities\OrderItem;
use Modules\Order\Http\Entities\OrderStatus;
use Modules\Order\Http\Resources\OrderDetailResource;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Product\Http\Entities\Product;
use Modules\User\Http\Entities\Address;
use Modules\User\Http\Entities\Basket;
use App\Enums\OrderStatus as OrderStatusEnum;

class OrderService
{
    private Order $model;
    private DeliveryService $deliveryService;
    private BalanceService $balanceService;

    public function __construct(Order $model, DeliveryService $deliveryService, BalanceService $balanceService)
    {
        $this->model = $model;
        $this->deliveryService = $deliveryService;
        $this->balanceService = $balanceService;
    }

    public function getAll(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $orders = $this->model
            ->with(['items', 'latestStatus', 'address', 'user'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return responseHelper(
            __('Order data retrieved successfully.'),
            200,
            OrderResource::collection($orders)
        );
    }

    public function details(int $id): JsonResponse
    {
        try {
            $order = $this->model
                ->with([
                    'items' => function ($q) {
                        $q->with(['product', 'color', 'size']);
                    },
                    'statuses',
                    'address',
                    'user'
                ])
                ->where('user_id', auth()->id())
                ->findOrFail($id);
            return responseHelper( 'Order details retrieved successfully.',200,new OrderDetailResource($order));

        } catch (ModelNotFoundException $e) {
            return responseHelper('Order not found.',404);
        }
    }

    public function orderFromBasket($request): JsonResponse
    {
        $validated = $request->validated();

        try {

            $address = Address::where('user_id', auth()->id())
                ->where('is_default', true)
                ->first();

            if (!$address) {
                return responseHelper('Please set a valid default address with a city before placing an order.', 400);
            }

            $deliveryResponse = $this->deliveryService
                ->details(null, $address->city)
                ->getData(true);

            $delivery = $deliveryResponse['data'] ?? null;

            if (!$delivery) {
                return responseHelper('Delivery service is not available for your city.', 400);
            }

            $basket = Basket::with(['product'])
                ->where('user_id', auth()->id())
                ->where('selected', true)
                ->get();

            if ($basket->isEmpty()) {
                return response()->json([
                    'success' => 400,
                    'message' => __('Your basket is empty.'),
                ], 400);
            }

            foreach ($basket as $item) {
                if ($item->product->stock_count < $item->quantity) {
                    return responseHelper("Insufficient stock for product: $item->product->title['az']", 400);
                }
            }

            $validated['address_id'] = $address->id;
            $validated['user_id'] = auth()->id();
            $validated['transaction_id'] = (string)strtoupper(Str::uuid());

            $validated['total_price'] = round($basket->sum(function ($item) {
                $price = $item->product->discount ?? null;
                return $item->quantity * (($price && $price > 0) ? $price : $item->product->price);
            }), 2);

            $validated['discount_price'] = round($basket->sum(function ($item) {
                if (!empty($item->product->discount) && $item->product->discount > 0) {
                    return $item->quantity * ($item->product->price - $item->product->discount);
                }
                return 0;
            }), 2);
            $validated['shipping_price'] = $validated['total_price'] < $delivery['free_from'] ? ($delivery['price'] ?? 0) : 0;

            if (isset($validated['pay_with_balance']) && $validated['pay_with_balance']) {
                $userBalance = $this->balanceService->getBalance()->getData(true)['data']['balance'] ?? 0;

                if ($userBalance < ($validated['total_price'] + $validated['shipping_price'])) {
                    return responseHelper('Insufficient balance to complete the order.', 400);
                }

                $balanceResponse = $this->balanceService->withdraw($validated['user_id'], ($validated['total_price'] + $validated['shipping_price']), 'Payment for order with transaction ID: ' . $validated['transaction_id']);
                $balanceContent = $balanceResponse->getData(true);

                if ($balanceContent['success'] !== 201) {
                    return responseHelper('Failed to process payment from balance. Please try again.', 500);
                }
            unset($validated['pay_with_balance']);
            }

            $validated['paid_at'] = now();

            $data = handleTransaction(
                fn() => $this->model->create($validated)->refresh(),
                'Order added successfully.'
            );

            $content = $data->getData(true);

            if ($content['success'] === 201) {
                $orderId = (int)$content['data']['id'];

                handleTransaction(
                    fn() => OrderStatus::create([
                        'order_id' => $orderId,
                        'status' => OrderStatusEnum::PLACED,
                    ])->refresh(),
                );

                foreach ($basket as $item) {
                    handleTransaction(
                        fn() => OrderItem::create([
                            'order_id' => $orderId,
                            'product_id' => $item->product->id,
                            'color_id' => $item->color_id,
                            'size_id' => $item->size_id,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->product->discount ?? $item->product->price,
                            'total_price' => ($item->product->discount ?? $item->product->price) * $item->quantity,
                        ])
                    );

                    Product::where('id', $item->product->id)
                        ->decrement('stock_count', $item->quantity);

                    Product::where('id', $item->product->id)
                        ->increment('sales_count', $item->quantity);
                }

                Basket::destroy($basket->pluck('id')->toArray());
            }

            return responseHelper('Order added successfully.', 201);


        } catch (\Throwable $e) {
            \Log::error('Order creation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return responseHelper('Something went wrong while placing the order.', 500);

        }
    }

    public function buyOne($request, $product_id): JsonResponse
    {
        $validated = $request->validated();
        try {

            $address = Address::where('user_id', auth()->id())
                ->where('is_default', true)
                ->first();

            if (!$address) {
                return responseHelper('Please set a valid default address with a city before placing an order.', 400);
            }

            $deliveryResponse = $this->deliveryService
                ->details(null, $address->city)
                ->getData(true);

            $delivery = $deliveryResponse['data'] ?? null;

            if (!$delivery) {
                return responseHelper('Delivery service is not available for your city.', 400);
            }

            $product = Product::findOrFail($product_id);

            if ($product->stock_count < 1) {
                return responseHelper("Insufficient stock for product: $product->title['az']", 400);
            }

            $validated['address_id'] = $address->id;
            $validated['user_id'] = auth()->id();
            $validated['transaction_id'] = (string)strtoupper(Str::uuid());

            $price = $product->discount && $product->discount > 0 ? $product->discount : $product->price;

            $validated['total_price'] = round($price, 2);
            $validated['discount_price'] = $product->discount && $product->discount > 0 ? round($product->price - $product->discount, 2) : 0;

            $validated['shipping_price'] = $validated['total_price'] < $delivery['free_from'] ? ($delivery['price'] ?? 0) : 0;

            if (isset($validated['pay_with_balance']) && $validated['pay_with_balance']) {
                $userBalance = $this->balanceService->getBalance()->getData(true)['data']['balance'] ?? 0;

                if ($userBalance < ($validated['total_price'] + $validated['shipping_price'])) {
                    return responseHelper('Insufficient balance to complete the order.', 400);
                }

                $balanceResponse = $this->balanceService->withdraw(
                    $validated['user_id'],
                    ($validated['total_price'] + $validated['shipping_price']),
                    'Payment for order with transaction ID: ' . $validated['transaction_id']
                );

                $balanceContent = $balanceResponse->getData(true);

                if ($balanceContent['success'] !== 201) {
                    return responseHelper( 'Failed to process payment from balance. Please try again.', 500);
                }
            unset($validated['pay_with_balance']);
            }

            $validated['paid_at'] = now();

            $data = handleTransaction(
                fn() => $this->model->create($validated)->refresh(),
                'Order added successfully.'
            );

            $content = $data->getData(true);

            if ($content['success'] === 201) {
                $orderId = (int)$content['data']['id'];

                handleTransaction(
                    fn() => OrderStatus::create([
                        'order_id' => $orderId,
                        'status' => OrderStatusEnum::PLACED,
                    ])->refresh(),
                );

                handleTransaction(
                    fn() => OrderItem::create([
                        'order_id' => $orderId,
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'unit_price' => $price,
                        'total_price' => $price,
                    ])
                );

                Product::where('id', $product->id)->decrement('stock_count', 1);
                Product::where('id', $product->id)->increment('sales_count', 1);
            }

            return responseHelper( 'Order added successfully.', 201);

        } catch (\Throwable $e) {
            \Log::error('Order creation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return responseHelper('Something went wrong while placing the order.', 500);

        }
    }

    public function update(int $id, $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['status'] = OrderStatusEnum::from($data['status'])->value;
            $order = Order::findOrFail($id);
            return handleTransaction(
                fn() => OrderStatus::create([
                    'order_id' => $id,
                    'status' => $data['status'],
                ])->refresh(),
                'Order status successfully.',
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Order not found.'),
            ], 404);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $order = $this->model
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            return handleTransaction(
                fn() => tap($order)->delete(),
                'Order deleted successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Order not found.'),
            ], 404);
        }
    }
}
