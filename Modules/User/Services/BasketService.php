<?php

namespace Modules\User\Services;

use App\Interfaces\ICrudInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Http\Entities\Product;
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
            ->latest()
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

        $product = Product::find($validated['product_id']);

        if (!$product) {
            return responseHelper('Product not found.', 403);
        }

        if (!$product->colors()->exists()) {
            $validated['color_id'] = null;
        }

        if (!$product->sizes()->exists()) {
            $validated['size_id'] = null;
        }

        if (isset($validated['color_id']) && $product->colors()->exists()) {
            if (!$product->colors()->where('color_id', $validated['color_id'])->exists()) {
                return responseHelper('Selected color is not available for this product.', 403);
            }
        }

        if (isset($validated['size_id']) && $product->sizes()->exists()) {
            if (!$product->sizes()->where('size_id', $validated['size_id'])->exists()) {
                return responseHelper('Selected size is not available for this product.', 403);
            }
        }

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

            if (!isset($data['color_id'])) {
                unset($data['color_id']);
            }
            if (!isset($data['size_id'])) {
                unset($data['size_id']);
            }

            return handleTransaction(
                fn() => tap($basket)->update($data)->refresh(),
                'Basket updated successfully.',
                BasketResource::class
            );
        } catch (ModelNotFoundException $e) {
            return responseHelper('Basket not found.', 403, []);
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
            return responseHelper('Basket not found.', 403, []);
        }
    }
}
