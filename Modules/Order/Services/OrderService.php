<?php

namespace Modules\Order\Services;

use App\Enums\BalanceType;
use App\Interfaces\ICrudInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Balance\Services\BalanceService;
use Modules\Delivery\Services\DeliveryService;
use Modules\Order\Http\Entities\Order;
use Modules\Order\Http\Entities\OrderItem;
use Modules\Order\Http\Entities\OrderStatus;
use Modules\Order\Http\Resources\OrderDetailResource;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Product\Http\Entities\Product;
use Modules\PromoCode\Services\PromoCodeService;
use Modules\User\Http\Entities\Address;
use Modules\User\Http\Entities\Basket;
use App\Enums\OrderStatus as OrderStatusEnum;

class OrderService
{
    private Order $model;
    private DeliveryService $deliveryService;
    private BalanceService $balanceService;
    private PromoCodeService $promoCodeService;

    public function __construct(Order $model, DeliveryService $deliveryService, BalanceService $balanceService, PromoCodeService $promoCodeService)
    {
        $this->model = $model;
        $this->deliveryService = $deliveryService;
        $this->balanceService = $balanceService;
        $this->promoCodeService = $promoCodeService;
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

    public function getAllAdmin(Request $request): JsonResponse
    {

        $orders = $this->model
            ->with(['items', 'latestStatus', 'address', 'user'])
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
                        $q->with([
                            'product' => function ($query) {
                                $query->with([
                                    'brand',
                                    'category',
                                    'images',
                                    'colors',
                                    'sizes',
                                    'reviews' => function ($q) {
                                        $q->select('id', 'product_id', 'user_id', 'rate', 'comment', 'created_at');
                                    }
                                ])
                                    ->withAvg('reviews', 'rate')
                                    ->withCount('reviews');
                            },
                            'color',
                            'size'
                        ]);
                    },
                    'statuses',
                    'address',
                    'user'
                ])
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            $order->items->transform(function ($item) {
                $product = $item->product;
                if ($product) {
                    $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
                    $product->rate_count = $product->reviews_count;

                    $product->is_favorite = Auth::check()
                        ? $product->favoritedBy()->where('user_id', Auth::id())->exists()
                        : false;
                }
                return $item;
            });

            return responseHelper('Order details retrieved successfully.', 200, OrderDetailResource::make($order));

        } catch (ModelNotFoundException $e) {
            return responseHelper('Order not found.', 404);
        }
    }

//    public function orderFromBasket($request): JsonResponse
//    {
//        $validated = $request->validated();
//
//        try {
//
//            $address = Address::where('user_id', auth()->id())
//                ->where('is_default', true)
//                ->first();
//
//            if (!$address) {
//                return responseHelper('Please set a valid default address with a city before placing an order.', 400);
//            }
//
//            $deliveryResponse = $this->deliveryService
//                ->details(null, $address->city)
//                ->getData(true);
//
//            $delivery = $deliveryResponse['data'] ?? null;
//
//            if (!$delivery) {
//                return responseHelper('Delivery service is not available for your city.', 400);
//            }
//
//            $basket = Basket::with(['product'])
//                ->where('user_id', auth()->id())
//                ->where('selected', true)
//                ->get();
//
//            if ($basket->isEmpty()) {
//                return response()->json([
//                    'success' => 400,
//                    'message' => __('Your basket is empty.'),
//                ], 400);
//            }
//
//            foreach ($basket as $item) {
//                if ($item->product->stock_count < $item->quantity) {
//                    return responseHelper("Insufficient stock for product: $item->product->title['az']", 400);
//                }
//            }
//
//            $validated['address_id'] = $address->id;
//            $validated['user_id'] = auth()->id();
//            $validated['transaction_id'] = (string)strtoupper(Str::uuid());
//
//            $validated['total_price'] = round($basket->sum(function ($item) {
//                $price = $item->product->discount ?? null;
//                return $item->quantity * (($price && $price > 0) ? $price : $item->product->price);
//            }), 2);
//
//            $validated['discount_price'] = round($basket->sum(function ($item) {
//                if (!empty($item->product->discount) && $item->product->discount > 0) {
//                    return $item->quantity * ($item->product->price - $item->product->discount);
//                }
//                return 0;
//            }), 2);
//            $validated['shipping_price'] = $validated['total_price'] < $delivery['free_from'] ? ($delivery['price'] ?? 0) : 0;
//
//            if (isset($validated['pay_with_balance']) && $validated['pay_with_balance']) {
//                $userBalance = $this->balanceService->getBalance()->getData(true)['data']['balance'] ?? 0;
//
//                if ($userBalance < ($validated['total_price'] + $validated['shipping_price'])) {
//                    return responseHelper('Insufficient balance to complete the order.', 400);
//                }
//
//                $balanceResponse = $this->balanceService->withdraw($validated['user_id'], ($validated['total_price'] + $validated['shipping_price']), 'Payment for order with transaction ID: ' . $validated['transaction_id']);
//                $balanceContent = $balanceResponse->getData(true);
//
//                if ($balanceContent['success'] !== 201) {
//                    return responseHelper('Failed to process payment from balance. Please try again.', 500);
//                }
//                unset($validated['pay_with_balance']);
//            }
//
//            $validated['paid_at'] = now();
//
//            $data = handleTransaction(
//                fn() => $this->model->create($validated)->refresh(),
//                'Order added successfully.'
//            );
//
//            $content = $data->getData(true);
//            if ($content['success']) {
//                $orderId = (int)$content['data']['id'];
//
//                handleTransaction(
//                    fn() => OrderStatus::create([
//                        'order_id' => $orderId,
//                        'status' => OrderStatusEnum::PLACED,
//                    ])->refresh(),
//                );
//
//                foreach ($basket as $item) {
//                    handleTransaction(
//                        fn() => OrderItem::create([
//                            'order_id' => $orderId,
//                            'product_id' => $item->product->id,
//                            'color_id' => $item->color_id,
//                            'size_id' => $item->size_id,
//                            'quantity' => $item->quantity,
//                            'unit_price' => $item->product->discount ?? $item->product->price,
//                            'total_price' => ($item->product->discount ?? $item->product->price) * $item->quantity,
//                        ])
//                    );
//
//                    Product::where('id', $item->product->id)
//                        ->decrement('stock_count', $item->quantity);
//
//                    Product::where('id', $item->product->id)
//                        ->increment('sales_count', $item->quantity);
//                }
//
//                Basket::destroy($basket->pluck('id')->toArray());
//            }
//
//            return responseHelper('Order added successfully.', 201);
//
//
//        } catch (\Throwable $e) {
//            \Log::error('Order creation failed', [
//                'user_id' => auth()->id(),
//                'error' => $e->getMessage(),
//            ]);
//
//            return responseHelper('Something went wrong while placing the order.', 500);
//
//        }
//    }

    public function orderFromBasket($request): JsonResponse
    {
        $validated = $request->validated();
        $user = auth()->user();

        try {
            $address = Address::where('user_id', $user->id)
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

            $basket = Basket::with('product')
                ->where('user_id', $user->id)
                ->where('selected', true)
                ->get();

            if ($basket->isEmpty()) {
                return responseHelper('Your basket is empty.', 400);
            }

            foreach ($basket as $item) {
                $product = $item->product;
                if (!$product) {
                    return responseHelper('Product not found.', 404);
                }
                $title = is_array($product->title)
                    ? ($product->title['az'] ?? reset($product->title))
                    : $product->title;

                if ($item->color_id && !$product->colors()->where('color_id', $item->color_id)->exists()) {
                    \Log::warning('Invalid color for product');

                    return responseHelper("Selected color is not available for product: {$title}", 400);
                }

                if ($item->size_id && !$product->sizes()->where('size_id', $item->size_id)->exists()) {
                    \Log::warning('Invalid size for product');

                    return responseHelper("Selected size is not available for product: {$title}", 400);
                }

                if ($product->stock_count < $item->quantity) {
                    \Log::warning('Insufficient stock for product');

                    return responseHelper("Insufficient stock for product: {$title}", 400);
                }

            }

            $validated['address_id'] = $address->id;
            $validated['user_id'] = $user->id;
            $validated['transaction_id'] = (string)strtoupper(Str::uuid());

            $validated['total_price'] = round($basket->sum(function ($item) {
                return $item->quantity * (($item->product->discount ?? 0) > 0 ? $item->product->discount : $item->product->price);
            }), 2);

            $validated['discount_price'] = round($basket->sum(function ($item) {
                if (($item->product->discount ?? 0) > 0) {
                    return $item->quantity * ($item->product->price - $item->product->discount);
                }
                return 0;
            }), 2);

            $validated['shipping_price'] = $validated['total_price'] < $delivery['free_from'] ? ($delivery['price'] ?? 0) : 0;

            if (!empty($validated['promo_code'])) {
                $response = $this->promoCodeService->check($validated['promo_code'], true);

                if ($response->getData(true)['status_code'] == 200) {
                    $promoData = $response->getData(true)['data'];

                    if (($promoData['discount_percent'] ?? 0) > 0) {
                        $discountAmount = ($validated['total_price'] * $promoData['discount_percent']) / 100;
                        $validated['total_price'] = round($validated['total_price'] - $discountAmount, 2);
                    }

                    $this->promoCodeService->applyPromoCodeToUser($promoData['id'], $user->id, true);
                } else {
                    return responseHelper($response->getData(true)['message'], $response->getData(true)['status_code']);
                }
                unset($validated['promo_code']);
            }

            if (!empty($validated['pay_with_balance'])) {
                $userBalance = $this->balanceService->getBalance()->getData(true)['data']['balance'] ?? 0;

                if ($userBalance < ($validated['total_price'] + $validated['shipping_price'])) {
                    return responseHelper('Insufficient balance to complete the order.', 400);
                }

                $balanceResponse = $this->balanceService->withdraw(
                    $user->id,
                    ($validated['total_price'] + $validated['shipping_price']),
                    "Payment for order with transaction ID: {$validated['transaction_id']}"
                );

                $balanceContent = $balanceResponse->getData(true);

                if (!($balanceContent['success'] && $balanceContent['status_code'] === 201)) {
                    return responseHelper('Failed to process payment from balance. Please try again.', 500);
                }

                unset($validated['pay_with_balance']);
            }

            $validated['paid_at'] = now();

            $data = handleTransaction(
                fn() => $this->model->create($validated)->refresh(),
                'Order added successfully.',
                null,
                201
            );

            $content = $data->getData(true);

            if ($content['status_code'] === 201) {
                $orderId = (int)$content['data']['id'];

                handleTransaction(
                    fn() => OrderStatus::create([
                        'order_id' => $orderId,
                        'status' => OrderStatusEnum::PLACED,
                    ])
                );

                foreach ($basket as $item) {
                    $product = $item->product;
                    $price = ($product->discount ?? 0) > 0 ? $product->discount : $product->price;

                    handleTransaction(
                        fn() => OrderItem::create([
                            'order_id' => $orderId,
                            'product_id' => $product->id,
                            'color_id' => $item->color_id,
                            'size_id' => $item->size_id,
                            'quantity' => $item->quantity,
                            'unit_price' => $price,
                            'total_price' => $price * $item->quantity,
                        ])
                    );

                    $product->decrement('stock_count', $item->quantity);
                    $product->increment('sales_count', $item->quantity);
                }

                Basket::destroy($basket->pluck('id')->toArray());
            }

            return responseHelper('Order added successfully.', 201);

        } catch (\Throwable $e) {
            \Log::error('Order creation failed', [
                'user_id'         => $user->id,
                'exception_class' => get_class($e),
                'error_message'   => $e->getMessage(),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
                'trace'           => collect($e->getTrace())->take(10)->toArray(), // ilk 10 satır
            ]);


            return responseHelper('Something went wrong while placing the order.', 500);
        }
    }


    public function buyOne($request, $product_id): JsonResponse
    {
        $validated = $request->validated();
        $color_id = $request->color_id ?? null;
        $size_id = $request->size_id ?? null;
        if ($color_id)unset($validated['color_id']);
        if ($size_id)unset($validated['size_id']);
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

            if ($color_id) if (!$product->colors()->where('color_id',$color_id)->exists()){
                return responseHelper('Selected color is not available for this product.', 400);
            }
            if ($size_id) if (!$product->sizes()->where('size_id',$size_id)->exists()){
                return responseHelper('Selected size is not available for this product.', 400);
            }

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

            if (isset($validated['promo_code']) && $validated['promo_code']) {
                if ($product->discount && $product->discount > 0) {
                    return responseHelper('Promo codes cannot be applied to already discounted products.', 400);
                }
                $response = $this->promoCodeService->check($validated['promo_code'], true);

                if ($response->getData(true)['status_code'] == 200) {
                   $promoData = $response->getData(true)['data'];
                   if ($promoData['discount_percent'] && $promoData['discount_percent'] > 0) {
                       $discountAmount = ( $product->price * $promoData['discount_percent']) / 100;
                       $validated['total_price'] = round($validated['total_price'] - $discountAmount, 2);
                    }
                      $this->promoCodeService->applyPromoCodeToUser($promoData['id'], $validated['user_id'],true);
                } else {
                    return responseHelper($response->getData(true)['message'], $response->getData(true)['status_code']);
                }
                unset($validated['promo_code']);
            }

            if (isset($validated['pay_with_balance']) && $validated['pay_with_balance']) {
                $userBalance = $this->balanceService->getBalance()->getData(true)['data']['balance'] ?? 0;

                if ($userBalance < ($validated['total_price'] + $validated['shipping_price'])) {
                    return responseHelper('Insufficient balance to complete the order.', 400);
                }

                $balanceResponse = $this->balanceService->withdraw(
                    $validated['user_id'],
                    ($validated['total_price'] + $validated['shipping_price']),
                    "Payment for order with transaction ID: {$validated['transaction_id']}"
                );

                $balanceContent = $balanceResponse->getData(true);

                if ($balanceContent['success'] && $balanceContent['status_code'] !== 201) {
                    return responseHelper('Failed to process payment from balance. Please try again.', 500);
                }
                unset($validated['pay_with_balance']);
            }

            $validated['paid_at'] = now();

            $data = handleTransaction(
                fn() => $this->model->create($validated)->refresh(),
                'Order added successfully.',
                null,
                201
            );

            $content = $data->getData(true);


            if ($content['status_code'] === 201) {
//            return response()->json($content);
                $orderId = (int)$content['data']['id'];

                handleTransaction(
                    fn() => OrderStatus::create([
                        'order_id' => $orderId,
                        'status' => OrderStatusEnum::PLACED,
                    ])->refresh(),
                );
                $product->decrement('stock_count', 1);
                $product->increment('sales_count', 1);

                handleTransaction(
                    fn() => OrderItem::create([
                        'order_id' => $orderId,
                        'product_id' => $product->id,
                        'color_id' => $color_id,
                        'size_id' => $size_id,
                        'quantity' => 1,
                        'unit_price' => $price,
                        'total_price' => $price,
                    ])
                );

                //  Product::where('id', $product->id)->decrement('stock_count', 1);
                // Product::where('id', $product->id)->increment('sales_count', 1);
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
    public function getReceipt(int $orderId): JsonResponse
    {
        try {
            $order = Order::with(['items.product', 'address'])
                ->where('id', $orderId)
                ->where('user_id', auth()->id())
                ->first();

            if (!$order) {
                return responseHelper('Order not found.', 404);
            }

            $orderSummary = [
                'order_id'       => $order->id,
                'transaction_id'       => $order->transaction_id,
                'order_time'     => $order->created_at->format('Y-m-d H:i:s'),
                'items_totals'   => $order->items->sum(fn($item) => $item->total_price),
                'items_discounts'=> $order->discount_price ?? 0,
                'shipping'       => $order->shipping_price ?? 0,
                'total'          => ($order->total_price + ($order->shipping_price ?? 0)) - ($order->discount_price ?? 0),
            ];

            $pickup = [
                'city'      => $order->address->city ?? null,
                'town'      => $order->address->town_village_district ?? null,
                'street'    => $order->address->street_building_number ?? null,
                'apartment' => $order->address->unit_floor_apartment ?? null,
                'phone'     => $order->address->contact_number ?? auth()->user()->phone,
            ];
            return responseHelper('Order receipt generated successfully.', 200, [
                'order_summary' => $orderSummary,
                'pickup'        => $pickup,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Order receipt failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return responseHelper('Something went wrong while generating the receipt.', 500);
        }
    }

    public function downloadReceipt(int $orderId)
    {
        try {
            $user = auth()->user();

            $order = Order::with(['items.product', 'address'])
                ->where('id', $orderId)
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return responseHelper('Order not found.', 404);
            }

            $orderSummary = [
                'order_id'        => $order->id,
                'transaction_id'  => $order->transaction_id,
                'order_time'      => $order->created_at->format('Y-m-d H:i:s'),
                'items_totals'    => $order->items->sum(fn($item) => $item->total_price),
                'items_discounts' => $order->discount_price ?? 0,
                'shipping'        => $order->shipping_price ?? 0,
                'total'           => ($order->total_price + ($order->shipping_price ?? 0)) - ($order->discount_price ?? 0),
            ];

            $pickup = [
                'city'      => $order->address->city ?? null,
                'town'      => $order->address->town_village_district ?? null,
                'street'    => $order->address->street_building_number ?? null,
                'apartment' => $order->address->unit_floor_apartment ?? null,
                'phone'     => $order->address->contact_number ?? $user->phone,
            ];

            foreach ($order->items as $item) {
                $item->product_title = mb_convert_encoding(
                    $item->product->getTranslation('title', app()->getLocale()),
                    'UTF-8', 'UTF-8'
                );
            }
            $htmlContent = receiptPdf($order, $pickup, $orderSummary);

            $pdf = Pdf::loadHTML($htmlContent);
            $filename = "receipt_order_{$order->transaction_id}.pdf";

            return $pdf->download($filename);

        } catch (\Throwable $e) {
            \Log::error('Receipt PDF generation failed', [
                'user_id' => $user->id ?? null,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return responseHelper('Something went wrong while generating the receipt PDF.', 500);
        }
    }

}
