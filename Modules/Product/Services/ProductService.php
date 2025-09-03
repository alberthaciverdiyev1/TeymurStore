<?php

namespace Modules\Product\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Entities\ProductImage;
use Modules\Product\Http\Resources\ProductResource;
use Illuminate\Support\Str;

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

        $query = Product::query()
            ->with(['colors', 'sizes', 'images', 'category', 'brand']);

        // Aktiv / discount
        rangeFilter($query, 'is_active', $params);
        if (!empty($params['discount'])) {
            $query->whereNotNull('discount');
        }

        // Category, brand, gender
        whereEach($query, ['category_id', 'brand_id', 'gender'], $params);

        // Price aralığı
        rangeFilter($query, 'price', $params);

        // Rəng və ölçü (pivot table)
        if (!empty($params['color_ids']) && is_array($params['color_ids'])) {
            $query->whereHas('colors', fn($q) => $q->whereIn('colors.id', $params['color_ids']));
        }
        if (!empty($params['size_ids']) && is_array($params['size_ids'])) {
            $query->whereHas('sizes', fn($q) => $q->whereIn('sizes.id', $params['size_ids']));
        }

        // Axtarış title/description bütün dillərdə
        if (!empty($params['search'])) {
            filterLike($query, ['title', 'description'], $params); // filterLike artıq bütün dillərdə axtarır
        }

        // Sıralama
        orderBy($query, $params);

        $data = $query->paginate(20);

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

        $data['title'] = array_map(static fn($v) => Str::lower($v), $data['title'] ?? []);
        $data['description'] = array_map(static fn($v) => Str::lower($v), $data['description'] ?? []);

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

        Cache::forget('products_list_all');

        return $product;
    }

    /**
     * Update product
     */
    /**
     * Update product
     */
    public function update($request, int $id): JsonResponse
    {
        $data = $request->validated();
        $data['title'] = array_map(fn($v) => Str::lower($v), $data['title'] ?? []);
        $data['description'] = array_map(fn($v) => Str::lower($v), $data['description'] ?? []);

        $product = handleTransaction(function () use ($data, $request, $id) {
            $product = $this->model->findOrFail($id);

            $images_arr = $request->hasFile('images') ? $request->file('images') : [];

            // Translations
            $translations = [
                'title' => $data['title'] ?? $product->title,
                'description' => $data['description'] ?? $product->description,
            ];
            unset($data['title'], $data['description'], $data['images']);

            $product->update($data);

            $product->update($translations);

            // Colors sync
            if (array_key_exists('colors', $data)) {
                $product->colors()->sync($data['colors'] ?? []);
            }

            // Sizes sync
            if (array_key_exists('sizes', $data)) {
                $product->sizes()->sync($data['sizes'] ?? []);
            }

            if (!empty($images_arr)) {
                $product->images()->delete();

                $images = [];
                foreach ($images_arr as $image) {
                    $path = $image->store('products', 'public');
                    $images[] = ['image_path' => $path];
                }
                $product->images()->createMany($images);
            }

            return $product->refresh();
        }, 'Product updated successfully.', ProductResource::class);

        Cache::forget('products_list_all');

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
