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

class OrderService implements ICrudInterface
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

    public function getAll($request): JsonResponse
    {
        $userId = auth()->id();

        $data = $this->model
            ->with(['items', 'latestStatus', 'address', 'user'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return response()->json([
            'success' => 200,
            'message' => __('Order data retrieved successfully.'),
            'data' => OrderResource::collection($data),
        ]);
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
            return response()->json(data: [
                'success' => 200,
                'message' => __('Order details retrieved successfully.'),
                'data' => new OrderDetailResource($order),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Order not found.'),
            ], 404);
        }
    }

    public function add($request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $address = Address::where('user_id', auth()->id())
                ->where('is_default', true)
                ->first();

            if (!$address) {
                return response()->json([
                    'success' => 400,
                    'message' => __('Please set a valid default address with a city before placing an order.'),
                ], 400);
            }

            $deliveryResponse = $this->deliveryService
                ->details(null, $address->city)
                ->getData(true);

            $delivery = $deliveryResponse['data'] ?? null;

            if (!$delivery) {
                return response()->json([
                    'success' => 400,
                    'message' => __('Delivery service is not available for your city.'),
                ], 400);
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
                    return response()->json([
                        'success' => 400,
                        'message' => __('Insufficient stock for product: :product', [
                            'product' => $item->product->title['az'] ?? $item->product->sku,
                        ]),
                    ], 400);
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

            if ($validated['pay_with_balance']) {
                $userBalance = $this->balanceService->getBalance()->getData(true)['data']['balance'] ?? 0;

                if ($userBalance < ($validated['total_price'] + $validated['shipping_price'])) {
                    return response()->json([
                        'success' => 400,
                        'message' => __('Insufficient balance to complete the order.'),
                    ], 400);
                }

                $balanceResponse = $this->balanceService->withdraw($validated['user_id'],($validated['total_price'] + $validated['shipping_price']),'Payment for order with transaction ID: ' . $validated['transaction_id']);
                $balanceContent = $balanceResponse->getData(true);

                if ($balanceContent['success'] !== 201) {
                    return response()->json([
                        'success' => 500,
                        'message' => __('Failed to process payment from balance. Please try again.'),
                    ], 500);
                }
            }

            unset($validated['pay_with_balance']);
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

            return response()->json([
                'success' => 201,
                'message' => __('Order added successfully.'),
            ], 201);

        } catch (\Throwable $e) {
            \Log::error('Order creation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => 500,
                'message' => __('Something went wrong while placing the order.'),
            ], 500);
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
