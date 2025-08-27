<?php

namespace Modules\Brand\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\Brand\Http\Entities\Brand;
use Modules\Brand\Http\Transformers\BrandResource;

class BrandService
{
    private Brand $model;

    /**
     * @param Brand $model
     */
    function __construct(Brand $model)
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
        $cacheKey = 'brands_list_' . md5(serialize($params));

        $data = Cache::remember($cacheKey,config('cache.brand_list_cache_time'), function () use ($params) {
            $query = $this->model->query()->select(['id', 'name', 'image', 'is_active', 'sort_order']);
            $query = filterLike($query, ['name'], $params);

            if (isset($params['is_active'])) {
                $query->where('is_active', $params['is_active']);
            } else {
                $query->where('is_active', 1);
            }

            return $query->orderBy('created_at', 'desc')->paginate(20);
        });

        return response()->json([
            'success' => 200,
            'message' => __('Brands retrieved successfully.'),
            'data' => BrandResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * Brand details
     */
    public function details(int $id): JsonResponse
    {
        try {
            $brand = $this->model->findOrFail($id);

            return response()->json([
                'success' => 200,
                'message' => __('Brand details retrieved successfully.'),
                'data' => BrandResource::make($brand),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Brand not found.'),
                'data' => [],
            ]);
        }
    }

    /**
     * Add brand
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();

            if (!Storage::disk('public')->exists('brands')) {
                Storage::disk('public')->makeDirectory('brands', 0755, true);
            }

            $image->storeAs('brands', $imageName, 'public');
            $validated['image'] = 'brands/' . $imageName;
        }

        $brand = handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Brand added successfully.',
            BrandResource::class
        );

        Cache::forget('brands_list_*');

        return $brand;
    }

    /**
     * Update brand
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();

            if (!Storage::disk('public')->exists('brands')) {
                Storage::disk('public')->makeDirectory('brands', 0755, true);
            }

            $image->storeAs('brands', $imageName, 'public');
            $validated['image'] = 'brands/' . $imageName;
        }

        $brand = handleTransaction(
            function () use ($validated, $id) {
                $brand = $this->model->findOrFail($id);
                $brand->update($validated);
                return $brand->refresh();
            },
            'Brand updated successfully.',
            BrandResource::class
        );

        Cache::forget('brands_list_*');

        return $brand;
    }

    /**
     * Delete brand
     */
    public function delete(int $id): JsonResponse
    {
        $response = handleTransaction(
            function () use ($id) {
                $brand = $this->model->findOrFail($id);
                $brand->delete();
                return $brand;
            },
            'Brand deleted successfully.'
        );

        Cache::forget('brands_list_*');

        return $response;
    }
}
