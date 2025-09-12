<?php

namespace Modules\Order\Services;

use App\Interfaces\ICrudInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\Order\Http\Entities\Order;
use Modules\Order\Http\Entities\OrderItem;
use Modules\Order\Http\Entities\OrderStatus;
use Modules\Order\Http\Resources\OrderDetailResource;
use Modules\Order\Http\Resources\OrderResource;
use Modules\User\Http\Entities\Address;
use Modules\User\Http\Entities\Basket;
use App\Enums\OrderStatus as OrderStatusEnum;

class OrderService implements ICrudInterface
{
    private Order $model;

    public function __construct(Order $model)
    {
        $this->model = $model;
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

    /**
     * Add address
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        $basket = Basket::with(['product'])->where('user_id', auth()->id())->where('selected', true)->get();
        $validated['address_id'] = Address::where('user_id', auth()->id())->where('is_default', true)->value('id');
        $validated['user_id'] = auth()->id();
        $validated['transaction_id'] = (string)strtoupper(Str::uuid());
        $validated['total_price'] = round($basket->sum(function ($item) {
            $price = $item->product->discount ?? null;
            if ($price && $price > 0) {
                return $item->quantity * $price;
            }
            return $item->quantity * $item->product->price;
        }), 2);
        $validated['discount_price'] = $validated['total_price'] - round($basket->sum(function ($item) {
                if (!empty($item->product->discount) && $item->product->discount > 0) {
                    return $item->quantity * $item->product->discount;
                }
                return 0;
            }), 2);

        $validated['shipping_price'] = 0;
        $validated['paid_at'] = now();

        $data = handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Order added successfully.'
        );
        $content = $data->getData(true);
//        return response()->json($content);

        if ($content['success'] === 201) {
            (int)$orderId = $content['data']['id'];
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
            }
            Basket::destroy($basket->pluck('id')->toArray());
        }

        return response()->json([
            'success' => 201,
            'message' => __('Order added successfully.'),
        ], 201);
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
