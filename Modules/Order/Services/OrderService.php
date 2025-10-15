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
use Modules\Payment\Service\PaymentService;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Resources\ProductResource;
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
    private PaymentService $paymentService;

    public function __construct(Order $model, DeliveryService $deliveryService, BalanceService $balanceService, PromoCodeService $promoCodeService, PaymentService $paymentService)
    {
        $this->model = $model;
        $this->deliveryService = $deliveryService;
        $this->balanceService = $balanceService;
        $this->promoCodeService = $promoCodeService;
        $this->paymentService = $paymentService;
    }

    public function getAll(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $status = $request->query('status');

        $ordersQuery = $this->model
            ->with([
                'items' => function ($q) {
                    $q->with([
                        'product' => function ($query) {
                            $query->with([
                                'brand',
                                'category',
                                'images',
                                'reviews' => fn($q) => $q->select('id', 'product_id', 'user_id', 'rate', 'comment', 'created_at')
                            ])
                                ->withAvg('reviews', 'rate')
                                ->withCount('reviews');
                        },
                        'color',
                        'size'
                    ]);
                },
                'latestStatus',
                'address',
                'user'
            ])
            ->where('user_id', $userId);

        if ($status) {
            try {
                $statusEnum = OrderStatusEnum::fromString($status);
                $ordersQuery->whereHas('latestStatus', fn($q) => $q->where('status', $statusEnum->value));
            } catch (\InvalidArgumentException $e) {
                return responseHelper('Invalid status parameter.', 400);
            }
        }

        $ordersQuery = $ordersQuery->orderBy('id', 'desc');
        $orders = $ordersQuery->get();

        $orders->each(function ($order) {
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
        });

        return responseHelper(
            __('Order data retrieved successfully.'),
            200,
            OrderResource::collection($orders)
        );
    }


    public function completedOrders(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $orders = $this->model
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
                                'reviews' => fn($q) => $q->select('id', 'product_id', 'user_id', 'rate', 'comment', 'created_at')
                            ])
                                ->withAvg('reviews', 'rate')
                                ->withCount('reviews');
                        },
                        'color',
                        'size'
                    ]);
                },
                'latestStatus',
                'address',
                'user'
            ])
            ->where('user_id', $userId)
            ->whereHas('latestStatus', fn($q) => $q->where('status', OrderStatusEnum::DELIVERED->value))
            ->latest()
            ->get();

        $orders->each(function ($order) {
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
        });

        $products = $orders
            ->flatMap(fn($order) => $order->items->pluck('product'))
            ->filter()
            ->values();

        return responseHelper(
            __('Delivered products retrieved successfully.'),
            200,
            ProductResource::collection($products)
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

    public function orderFromBasket($request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        $appliedPromoId = null;
        $address_id = $request->address_id ?? null;
        $pay_with_balance = $request->pay_with_balance ?? false;
        $validated['paid_at'] = null;

        if ($address_id) unset($validated['address_id']);
        unset($validated['pay_with_balance']);

        try {

            $address = Address::where('user_id', $user->id)
                ->when($address_id, fn($q) => $q->where('id', $address_id),
                    fn($q) => $q->where('is_default', true))
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
                if (!$product) return responseHelper('Product not found.', 404);

                $title = is_array($product->title)
                    ? ($product->title['az'] ?? reset($product->title))
                    : $product->title;

                if ($item->color_id && !$product->colors()->where('color_id', $item->color_id)->exists()) {
                    return responseHelper("Selected color is not available for product: {$title}", 400);
                }

                if ($item->size_id && !$product->sizes()->where('size_id', $item->size_id)->exists()) {
                    return responseHelper("Selected size is not available for product: {$title}", 400);
                }

                if ($product->stock_count < $item->quantity) {
                    return responseHelper("Insufficient stock for product: {$title}", 400);
                }
            }

            $validated['address_id'] = $address->id;
            $validated['user_id'] = $user->id;
            $validated['transaction_id'] = (string)strtoupper(Str::uuid());

            $validated['total_price'] = round($basket->sum(function ($item) {
                return $item->quantity * (($item->product->discount ?? 0) > 0
                        ? $item->product->discount
                        : $item->product->price);
            }), 2);

            $validated['discount_price'] = round($basket->sum(function ($item) {
                return ($item->product->discount ?? 0) > 0
                    ? $item->quantity * ($item->product->price - $item->product->discount)
                    : 0;
            }), 2);

            $validated['shipping_price'] = $validated['total_price'] < $delivery['free_from']
                ? ($delivery['price'] ?? 0)
                : 0;

            if (!empty($validated['promo_code'])) {
                $response = $this->promoCodeService->check($validated['promo_code'], true);

                if ($response->getData(true)['status_code'] == 200) {
                    $promoData = $response->getData(true)['data'];

                    if (($promoData['discount_percent'] ?? 0) > 0) {
                        $discountAmount = ($validated['total_price'] * $promoData['discount_percent']) / 100;
                        $validated['total_price'] = round($validated['total_price'] - $discountAmount, 2);
                    }

                    $appliedPromoId = $promoData['id'];
                    $this->promoCodeService->applyPromoCodeToUser($appliedPromoId, $user->id, true);
                } else {
                    return responseHelper($response->getData(true)['message'], $response->getData(true)['status_code']);
                }
                unset($validated['promo_code']);
            }

            if ($pay_with_balance) {
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
                $validated['paid_at'] = now();
            }


            $data = handleTransaction(
                fn() => $this->model->create($validated)->refresh(),
                'Order added successfully.',
                null,
                201
            );

            $content = $data->getData(true);

            if ($content['status_code'] === 201) {
                $orderId = (int)$content['data']['id'];

                handleTransaction(fn() => OrderStatus::create([
                    'order_id' => $orderId,
                    'status' => $pay_with_balance ? OrderStatusEnum::PLACED : OrderStatusEnum::WAITING_PAYMENT,
                ]));

                foreach ($basket as $item) {
                    $product = $item->product;
                    $price = ($product->discount ?? 0) > 0 ? $product->discount : $product->price;

                    handleTransaction(fn() => OrderItem::create([
                        'order_id' => $orderId,
                        'product_id' => $product->id,
                        'color_id' => $item->color_id,
                        'size_id' => $item->size_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $price,
                        'total_price' => $price * $item->quantity,
                    ]));

                    $product->decrement('stock_count', $item->quantity);
                    $product->increment('sales_count', $item->quantity);
                }
                Basket::destroy($basket->pluck('id')->toArray());

                if ($appliedPromoId) {
                    \DB::table('used_promo_codes')
                        ->where('promo_code_id', $appliedPromoId)
                        ->where('user_id', $user->id)
                        ->whereNull('order_id')
                        ->update(['order_id' => $orderId]);
                }
            }
            if (!$pay_with_balance) {
                return responseHelper('Order redirected to payment page.', 200, [
                    'payment_url' => 'https://www.google.com',
                    'pay_with_balance' => $pay_with_balance
                ]);
            }

            return responseHelper('Order added successfully.', 200, [
                'payment_url' => '',
                'pay_with_balance' => $pay_with_balance
            ]);

        } catch (\Throwable $e) {
            if ($appliedPromoId) {
                \DB::table('used_promo_codes')
                    ->where('promo_code_id', $appliedPromoId)
                    ->where('user_id', $user->id)
                    ->whereNull('order_id')
                    ->delete();
            }

            \Log::error('Order creation failed', [
                'user_id' => $user->id,
                'exception_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(10)->toArray(),
            ]);

            return responseHelper('Something went wrong while placing the order.', 500, [
                'payment_url' => '',
            ]);
        }
    }


    public function buyOne($request, $product_id)
    {
        $validated = $request->validated();
        $color_id = $request->color_id ?? null;
        $size_id = $request->size_id ?? null;
        $address_id = $request->address_id ?? null;

        if ($color_id) unset($validated['color_id']);
        if ($size_id) unset($validated['size_id']);
        if ($address_id) unset($validated['address_id']);

        $appliedPromoId = null;

        try {
            $address = Address::where('user_id', auth()->id())
                ->when($address_id, fn($q) => $q->where('id', $address_id),
                    fn($q) => $q->where('is_default', true))
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

            if ($color_id && !$product->colors()->where('color_id', $color_id)->exists()) {
                return responseHelper('Selected color is not available for this product.', 400);
            }

            if ($size_id && !$product->sizes()->where('size_id', $size_id)->exists()) {
                return responseHelper('Selected size is not available for this product.', 400);
            }

            if ($product->stock_count < 1) {
                return responseHelper("Insufficient stock for product: {$product->title['az']}", 400);
            }

            $validated['address_id'] = $address->id;
            $validated['user_id'] = auth()->id();
            $validated['transaction_id'] = (string)strtoupper(Str::uuid());

            $price = $product->discount && $product->discount > 0 ? $product->discount : $product->price;

            $validated['total_price'] = round($price, 2);
            $validated['discount_price'] = $product->discount && $product->discount > 0 ? round($product->price - $product->discount, 2) : 0;
            $validated['shipping_price'] = $validated['total_price'] < $delivery['free_from'] ? ($delivery['price'] ?? 0) : 0;

            // ========================
            // PROMO CODE
            // ========================
            $promoData = null;
            if (!empty($validated['promo_code'])) {

                if ($product->discount && $product->discount > 0) {
                    return responseHelper('Promo codes cannot be applied to already discounted products.', 400);
                }

                $response = $this->promoCodeService->check($validated['promo_code'], true);

                if ($response->getData(true)['status_code'] !== 200) {
                    return responseHelper($response->getData(true)['message'], $response->getData(true)['status_code']);
                }

                $promoData = $response->getData(true)['data'];

                if (!empty($promoData['discount_percent']) && $promoData['discount_percent'] > 0) {
                    $discountAmount = ($product->price * $promoData['discount_percent']) / 100;
                    $validated['total_price'] = round($validated['total_price'] - $discountAmount, 2);
                }

                $appliedPromoId = $promoData['id'];
                $this->promoCodeService->applyPromoCodeToUser($appliedPromoId, $validated['user_id'], true);

                unset($validated['promo_code']);
            }

            // ========================
            // PAYMENT WITH BALANCE
            // ========================
            if (!empty($validated['pay_with_balance']) && $validated['pay_with_balance']) {
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
            } else {
                return 'https://www.google.com';
            }

            $validated['paid_at'] = now();

            // ========================
            // ORDER CREATE
            // ========================
            $data = handleTransaction(
                fn() => $this->model->create($validated)->refresh(),
                'Order added successfully.',
                null,
                201
            );

            $content = $data->getData(true);

            if ($content['status_code'] === 201) {
                $orderId = (int)$content['data']['id'];

                handleTransaction(fn() => OrderStatus::create([
                    'order_id' => $orderId,
                    'status' => OrderStatusEnum::PLACED,
                ])->refresh());

                $product->decrement('stock_count', 1);
                $product->increment('sales_count', 1);

                handleTransaction(fn() => OrderItem::create([
                    'order_id' => $orderId,
                    'product_id' => $product->id,
                    'color_id' => $color_id,
                    'size_id' => $size_id,
                    'quantity' => 1,
                    'unit_price' => $price,
                    'total_price' => $price,
                ]));

                // ========================
                // PROMO CODE PIVOT UPDATE
                // ========================
                if ($appliedPromoId) {
                    \DB::table('used_promo_codes')
                        ->where('promo_code_id', $appliedPromoId)
                        ->where('user_id', $validated['user_id'])
                        ->whereNull('order_id')
                        ->update(['order_id' => $orderId]);
                }
            }
            if (empty($validated['pay_with_balance'])) {
                return 'https://www.google.com';
            }

            return responseHelper('Order added successfully.', 201);

        } catch (\Throwable $e) {

            // ========================
            // PROMO CODE ROLLBACK
            // ========================
            if (!empty($appliedPromoId)) {
                \DB::table('used_promo_codes')
                    ->where('promo_code_id', $appliedPromoId)
                    ->where('user_id', $validated['user_id'])
                    ->whereNull('order_id')
                    ->delete();
            }

            \Log::error('Order creation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return responseHelper('Something went wrong while placing the order.', 500);
        }
    }

    public
    function update(int $id, $request): JsonResponse
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

    public
    function delete(int $id): JsonResponse
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
                'order_id' => $order->id,
                'transaction_id' => $order->transaction_id,
                'order_time' => $order->created_at->format('Y-m-d H:i:s'),
                'items_totals' => $order->items->sum(fn($item) => $item->total_price),
                'items_discounts' => $order->discount_price ?? 0,
                'shipping' => $order->shipping_price ?? 0,
                'total' => ($order->total_price + ($order->shipping_price ?? 0)) - ($order->discount_price ?? 0),
            ];

            $pickup = [
                'city' => $order->address->city ?? null,
                'town' => $order->address->town_village_district ?? null,
                'street' => $order->address->street_building_number ?? null,
                'apartment' => $order->address->unit_floor_apartment ?? null,
                'phone' => $order->address->contact_number ?? auth()->user()->phone,
            ];
            return responseHelper('Order receipt generated successfully.', 200, [
                'order_summary' => $orderSummary,
                'pickup' => $pickup,
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

            $usedPromo = \DB::table('used_promo_codes')
                ->where('user_id', $user->id)
                ->where('order_id', $order->id)
                ->first();

            $promoData = null;
            if ($usedPromo) {
                $promo = \Modules\PromoCode\Http\Entities\PromoCode::find($usedPromo->promo_code_id);
                if ($promo) {
                    $promoData = [
                        'code' => $promo->code,
                        'discount_percent' => $promo->discount_percent,
                    ];
                }
            }

            $orderSummary = [
                'order_id' => $order->id,
                'transaction_id' => $order->transaction_id,
                'order_time' => $order->created_at->format('Y-m-d H:i:s'),
                'items_totals' => $order->items->sum(fn($item) => $item->total_price),
                'items_discounts' => $order->discount_price ?? 0,
                'shipping' => $order->shipping_price ?? 0,
                'total' => ($order->total_price + ($order->shipping_price ?? 0)) - ($order->discount_price ?? 0),
                'promo' => $promoData,
            ];

            $pickup = [
                'city' => $order->address->city ?? null,
                'town' => $order->address->town_village_district ?? null,
                'street' => $order->address->street_building_number ?? null,
                'apartment' => $order->address->unit_floor_apartment ?? null,
                'phone' => $order->address->contact_number ?? $user->phone,
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
            $path = storage_path("app/public/receipts/{$filename}");

            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $pdf->save($path);

            $link = asset("storage/receipts/{$filename}");

            return response()->json([
                'success' => true,
                'file_link' => $link
            ]);

        } catch (\Throwable $e) {
            \Log::error('Receipt PDF generation failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return responseHelper('Something went wrong while generating the receipt PDF.', 500);
        }
    }


}
