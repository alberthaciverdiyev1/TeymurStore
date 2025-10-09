<?php

namespace Modules\User\Services;

use App\Interfaces\ICrudInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Http\Resources\ProductResource;
use Modules\User\Http\Entities\Basket;
use Modules\User\Http\Resources\BasketResource;

class BasketService implements ICrudInterface
{
    private Basket $model;

    public function __construct(Basket $model)
    {
        $this->model = $model;
    }

//    public function getAll($request): JsonResponse
//    {
//        $userId = auth()->id();
//
//        $data = $this->model
//            ->with([
//                'product' => function ($query) {
//                    $query->with(['reviews' => function ($q) {
//                        $q->select('id', 'product_id', 'user_id', 'rate', 'comment', 'created_at');
//                    }])
//                        ->withAvg('reviews', 'rate')
//                        ->withCount('reviews');
//                }
//            ])
//            ->where('user_id', $userId)
//            ->get()
//            ->map(function ($basketItem) {
//                if ($basketItem->product) {
//                    $product = $basketItem->product;
//
//                    $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
//                    $product->rate_count = $product->reviews_count;
//
//                    $product->is_favorite = Auth::check()
//                        ? $product->favoritedBy()->where('user_id', Auth::id())->exists()
//                        : false;
//                }
//
//                return $basketItem;
//            });
//
//        return responseHelper('Basket retrieved successfully.', 200, BasketResource::collection($data));
//    }
    public function getAll($request): JsonResponse
    {
        $userId = auth()->id();

        $basketItems = $this->model
            ->with([
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
                }
            ])
            ->where('user_id', $userId)
            ->get()
            ->map(function ($basketItem) {
                $product = $basketItem->product;

                if ($product) {
                    $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
                    $product->rate_count = $product->reviews_count;

                    $product->is_favorite = Auth::check()
                        ? $product->favoritedBy()->where('user_id', Auth::id())->exists()
                        : false;
                }

                return $basketItem;
            });

        return responseHelper(
            'Basket retrieved successfully.',
            200,
            BasketResource::collection($basketItems)
        );
    }


    public function details(int $id): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * Add address
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        return handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Basket added successfully.',
            BasketResource::class
        );
    }

    public function update(int $id, $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $basket = $this->model
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            return handleTransaction(
                fn() => tap($basket)->update($data)->refresh(),
                'Basket updated successfully.',
                BasketResource::class
            );
        } catch (ModelNotFoundException $e) {
            return responseHelper('Basket not found.', 404, []);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $basket = $this->model
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            return handleTransaction(
                fn() => tap($basket)->delete(),
                'Basket deleted successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return responseHelper('Basket not found.', 404, []);
        }
    }
}
