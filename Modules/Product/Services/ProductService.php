<?php

namespace Modules\Product\Services;

use App\Enums\Gender;
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

        // Product-ları lazımi relation-larla götür və review ortalaması və count əlavə et
        $query = Product::query()
            ->with(['colors', 'sizes', 'images', 'category', 'brand'])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews');

        // Aktiv məhsullar
        if (isset($params['is_active'])) {
            $query->where('is_active', $params['is_active']);
        } else {
            $query->where('is_active', 1);
        }

        // Endirimli məhsullar
        if (!empty($params['discount'])) {
            $query->whereNotNull('discount');
        }

        // Gender filter
        if (!empty($params['gender']) && in_array($params['gender'], ['male', 'female', 'kids'])) {
            $query->where('gender', Gender::fromString($params['gender'])->value);
        }

        // Qiymət aralığı filter
        rangeFilter($query, 'price', $params);

        // Category filter
        if (!empty($params['category_ids']) && is_array($params['category_ids'])) {
            $query->whereIn('category_id', $params['category_ids']);
        }

        // Brand filter
        if (!empty($params['brand_ids']) && is_array($params['brand_ids'])) {
            $query->whereIn('brand_id', $params['brand_ids']);
        }

        // Color filter
        if (!empty($params['color_ids']) && is_array($params['color_ids'])) {
            $query->whereHas('colors', fn($q) => $q->whereIn('colors.id', $params['color_ids']));
        }

        // Size filter
        if (!empty($params['size_ids']) && is_array($params['size_ids'])) {
            $query->whereHas('sizes', fn($q) => $q->whereIn('sizes.id', $params['size_ids']));
        }

        // Search filter
        if (!empty($params['search'])) {
            filterLike($query, ['title', 'description'], $params);
        }

        // Order by
        orderBy($query, $params);

        // Pagination
        $data = $query->paginate(20);

        // Query ilə gələn reviews_avg_rate null-dursa 5 qoy və review sayını əlavə et
        $data->getCollection()->transform(function ($product) {
            $product->rate = $product->reviews_avg_rate !== null
                ? round($product->reviews_avg_rate, 2)
                : 0;

            $product->rate_count = $product->reviews_count;
            return $product;
        });

        return response()->json([
            'success' => 200,
            'message' => __('Products retrieved successfully.'),
            'data' => ProductResource::collection($data),
            //'data' => $data,
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
                'colors', 'sizes', 'images', 'category', 'brand', 'reviews.user'
            ])->findOrFail($id);

            $averageRate = $product->reviews()->avg('rate') ?? 5;

            $data = ProductResource::make($product);
            $data->rate = round($averageRate, 2);

            return response()->json([
                'success' => 200,
                'message' => __('Product details retrieved successfully.'),
                'data' => $data,
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

            $product = $this->model->create($data);

            $product->update($translations);

            if (!empty($data['colors'])) {
                $product->colors()->sync($data['colors']);
            }

            if (!empty($data['sizes'])) {
                $product->sizes()->sync($data['sizes']);
            }

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
