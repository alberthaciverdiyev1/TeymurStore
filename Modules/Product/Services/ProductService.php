<?php

namespace Modules\Product\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Entities\ProductImage;
use Modules\Product\Http\Resources\ProductResource;

class ProductService
{
    private Product $model;

    /**
     * @param Product $model
     */
    function __construct(Product $model)
    {
        $this->model = $model;
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function list($request): JsonResponse
    {
        $params = $request->all();
        $locale = app()->getLocale();
        $cacheKey = 'products_list_' . md5(serialize($params) . $locale);

        $data = Cache::remember($cacheKey, config('cache.product_list_cache_time'), function () use ($params) {
            $query = Product::query()
                ->with(['colors', 'sizes', 'images', 'category', 'brand']);

            if (isset($params['is_active'])) {
                $query->where('is_active', $params['is_active']);
            } else {
                $query->where('is_active', 1);
            }

            return $query->orderBy('created_at', 'desc')->paginate(20);
        });

        return response()->json([
            'success' => 200,
            'message' => __('Products retrieved successfully.'),
            'data' => ProductResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * Product details
     */
    public function details(int $id): JsonResponse
    {
        try {
            $product = $this->model->with([
                'colors', 'sizes', 'images', 'category', 'user', 'brand'
            ])->findOrFail($id);

            return response()->json([
                'success' => 200,
                'message' => __('Product details retrieved successfully.'),
                'data' => ProductResource::make($product),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Product not found.'),
                'data' => [],
            ]);
        }
    }

    /**
     * Add product
     */
    public function add($request): JsonResponse
    {
        $data = $request->validated();

        $product = handleTransaction(function () use ($data, $request) {
            $images_arr = $request->hasFile('images') ? $request->file('images') : [];

            $translations = [
                'title' => $data['title'] ?? ['az' => ''],
                'description' => $data['description'] ?? ['az' => ''],
            ];
            unset($data['title'], $data['description'], $data['images']);

            // Product oluştur
            $product = $this->model->create($data);

            // Translations update et
            $product->update($translations);

            // Colors sync
            if (!empty($data['colors'])) {
                $product->colors()->sync($data['colors']);
            }

            // Sizes sync
            if (!empty($data['sizes'])) {
                $product->sizes()->sync($data['sizes']);
            }

            // Images upload
            if (!empty($images_arr) && is_array($images_arr)) {
                $images = [];
                foreach ($images_arr as $image) {
                    $path = $image->store('products', 'public');
                    $images[] = ['image_path' => $path];
                }
                $product->images()->createMany($images);
            }

            return $product->refresh();
        }, 'Product added successfully.', ProductResource::class);

        // Cache temizleme
        Cache::forget('products_list_all');

        return $product;
    }

    /**
     * Update product
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $product = handleTransaction(
            function () use ($validated, $id) {
                $product = $this->model->findOrFail($id);
                $product->update($validated);
                return $product->refresh();
            },
            'Product updated successfully.',
            ProductResource::class
        );

        Cache::forget('products_list_*');

        return $product;
    }

    /**
     * Delete product
     */
    public function delete(int $id): JsonResponse
    {
        $response = handleTransaction(
            function () use ($id) {
                $product = $this->model->findOrFail($id);
                $product->delete();
                return $product;
            },
            'Product deleted successfully.'
        );

        Cache::forget('products_list_*');

        return $response;
    }

}
