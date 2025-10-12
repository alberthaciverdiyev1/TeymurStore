<?php

namespace Modules\PromoCode\Services;

use App\Interfaces\ICrudInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\PromoCode\Http\Entities\PromoCode;
use Modules\PromoCode\Http\Resources\PromoCodeResource;
use Modules\User\Http\Entities\Basket;
use Modules\User\Http\Entities\User;

class PromoCodeService
{

    private PromoCode $model;

    /**
     * @param PromoCode $model
     */
    function __construct(PromoCode $model)
    {
        $this->model = $model;
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function getAll($request): JsonResponse
    {
        $params = $request->all();
        $cacheKey = 'promo_code_list_' . md5(serialize($params));

        $data = Cache::remember($cacheKey, config('promo_code_list_cache_time'), function () use ($params) {
            $query = $this->model->query()->select(['id', 'code', 'discount_percent', 'is_active', 'user_count', 'created_at']);
            $query = filterLike($query, ['code', 'discount_percent'], $params);

            if (isset($params['is_active'])) {
                $query->where('is_active', $params['is_active']);
            } else {
                $query->where('is_active', 1);
            }

            return $query->orderBy('created_at', 'asc')->get();
        });

        return response()->json([
            'success' => 200,
            'message' => __('Promo Codes retrieved successfully.'),
            'data' => PromoCodeResource::collection($data),
        ]);
    }

    public function details(int $id): JsonResponse
    {
        try {
            $promoCode = $this->model->findOrFail($id);

            return response()->json([
                'success' => 200,
                'message' => __('Promo Codes details retrieved successfully.'),
                'data' => PromoCodeResource::make($promoCode),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Promo Code not found.'),
                'data' => [],
            ]);
        }
    }

    public function check(string $code, $inline_request = false)
    {
        try {
            $user = auth()->user();

            $promoCode = $this->model
                ->where('code', $code)
                ->where('is_active', 1)
                ->first();

            if (!$promoCode) {
                return responseHelper('Promo Code not found.', 404, [], $inline_request);
            }

            if ($promoCode->user_count <= 0) {
                return responseHelper('Promo Code usage limit reached.', 400, [], $inline_request);
            }

            if ($user->usedPromoCodes()->where('promo_code_id', $promoCode->id)->exists()) {
                return responseHelper('You have already used this Promo Code.', 400, [], $inline_request);
            }

            return responseHelper('Promo Code checked successfully.', 200, PromoCodeResource::make($promoCode), $inline_request);

        } catch (\Exception $e) {
            return responseHelper('An error occurred.', 500, [], $inline_request);
        }
    }

    public function checkPromoCodeWithPrice(string $code, $request, bool $inline_request = false)
    {
        try {
            $user = auth()->user();

            $promoCode = $this->model
                ->where('code', $code)
                ->where('is_active', true)
                ->first();

            if (!$promoCode) {
                return responseHelper('Promo code not found.', 404, []);
            }

            if ($promoCode->user_count <= 0) {
                return responseHelper('Promo code usage limit reached.', 400, []);
            }

            if ($user->usedPromoCodes()->where('promo_code_id', $promoCode->id)->exists()) {
                return responseHelper('You have already used this promo code.', 400, []);
            }

            $basket = Basket::with('product')
                ->where('user_id', $user->id)
                ->where('selected', true)
                ->get();

            if ($basket->isEmpty()) {
                return responseHelper('Your basket is empty.', 400, []);
            }

            $totalPrice = round(
                $basket->sum(fn($item) => $item->quantity * ($item->product->discount > 0
                        ? $item->product->discount
                        : $item->product->price)),
                2
            );

            $discountedPrice = round($totalPrice * (1 - $promoCode->discount_percent / 100), 2);

            return responseHelper('Promo code checked successfully.', 200, [
                'original_price'  => $totalPrice,
             //   'discount_percent' => $promoCode->discount_percent,
                'discounted_price' => $discountedPrice,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Promo code check failed', [
                'code' => $code,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return responseHelper('An unexpected error occurred.', 500, []);
        }
    }


    /**
     * Add promoCode
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        $promoCode = handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Promo Code added successfully.',
            PromoCodeResource::class
        );

        Cache::forget('promo_code_list_' . md5(serialize([])));

        return $promoCode;
    }


    /**
     * Update promoCode
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $promoCode = handleTransaction(
            function () use ($validated, $id) {
                $promoCode = $this->model->findOrFail($id);
                $promoCode->update($validated);
                return $promoCode->refresh();
            },
            'Promo Code updated successfully.',
            PromoCodeResource::class
        );

        Cache::forget('promo_code_list_*');

        return $promoCode;
    }

    /**
     * Delete promoCode
     */
    public function delete(int $id): JsonResponse
    {
        $response = handleTransaction(
            function () use ($id) {
                $promoCode = $this->model->findOrFail($id);
                $promoCode->delete();
                return $promoCode;
            },
            'Promo Code deleted successfully.'
        );

        Cache::forget('promo_code_list_*');

        return $response;
    }

    public function applyPromoCodeToUser(int $promoCodeId, int $userId, bool $inline_request = false, int $orderId = null): bool
    {
        try {
            $promoCode = $this->model->findOrFail($promoCodeId);

            if ($promoCode->user_count > 0) {
                $promoCode->decrement('user_count');
            } else {
                return false;
            }

            $user = User::findOrFail($userId);

            if (!$user->usedPromoCodes()->where('promo_code_id', $promoCodeId)->exists()) {

                $pivotData = [];
                if ($orderId !== null) {
                    $pivotData['order_id'] = $orderId;
                }

                $user->usedPromoCodes()->attach($promoCodeId, $pivotData);

            } else {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            \Log::error("Failed to apply promo code {$promoCodeId} to user {$userId}: ".$e->getMessage());
            return false;
        }
    }

}
