<?php

namespace Modules\User\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Http\Entities\Product;

class FavoriteService
{
    private Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * List all favorites for the authenticated user
     */
    public function list(): JsonResponse
    {
        $user = Auth::user();

        $favorites = $user->favorites()
            ->with(['brand', 'category', 'images'])
            ->orderBy('user_favorites.created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => 200,
            'message' => __('Favorites retrieved successfully.'),
            'data'    => $favorites->items(),
            'meta'    => [
                'current_page' => $favorites->currentPage(),
                'last_page'    => $favorites->lastPage(),
                'per_page'     => $favorites->perPage(),
                'total'        => $favorites->total(),
            ],
        ]);
    }

    /**
     * Add product to favorites
     */
    public function add(int $productId): JsonResponse
    {
        $user = Auth::user();

        $product = $this->product->find($productId);
        if (!$product) {
            return response()->json([
                'success' => 404,
                'message' => __('Product not found.'),
            ], 404);
        }

        $user->favorites()->syncWithoutDetaching([$product->id]);

        return response()->json([
            'success' => 200,
            'message' => __('Product added to favorites.'),
            'data'    => $product,
        ]);
    }

    /**
     * Remove product from favorites
     */
    public function delete(int $productId): JsonResponse
    {
        $user = Auth::user();

        $product = $this->product->find($productId);

        if (!$product) {
            return response()->json([
                'success' => 404,
                'message' => __('Product not found.'),
            ], 404);
        }

        if ($user->favorites()->where('product_id', $product->id)->exists()) {
            $user->favorites()->detach($product->id);

            return response()->json([
                'success' => 200,
                'message' => __('Product removed from favorites.'),
            ]);
        }

        return response()->json([
            'success' => 200,
            'message' => __('Product was not in favorites.'),
        ]);
    }

}
