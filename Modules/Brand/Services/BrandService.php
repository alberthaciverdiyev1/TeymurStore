<?php

namespace Modules\Brand\Services;

use Illuminate\Http\JsonResponse;
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
        $query = $this->model->query()->select(['id', 'name', 'image', 'is_active', 'sort_order']);

        $query = filterLike($query, ['name'], $params);

        if (isset($params['is_active'])) {
            $query->where('is_active', $params['is_active']);
        }else{
            $query->where('is_active', 1);
        }

        $data = $query->orderBy('created_at', 'desc')->paginate(20);

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
     * @param int $id
     * @return JsonResponse
     */
    public function details(int $id): JsonResponse
    {
        try {
            $category = $this->model->findOrFail($id);

            return response()->json([
                'success' => 200,
                'message' => __('Brand details retrieved successfully.'),
                'data' => BrandResource::make($category),
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
     * @param $request
     * @return JsonResponse
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

        return handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Brand added successfully.',
            BrandResource::class
        );
    }

    /**
     * @param $request
     * @param int $id
     * @return JsonResponse
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        return handleTransaction(
            function () use ($validated, $id) {
                $category = $this->model->findOrFail($id);
                $category->update($validated);
                return $category;
            },
            'Brand updated successfully.',
            BrandResource::class
        );
    }


    /**
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        return handleTransaction(
            function () use ($id) {
                $category = $this->model->findOrFail($id);
                $category->delete();
                return $category;
            },
            'Brand deleted successfully.'
        );
    }

}
