<?php

namespace Modules\Color\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\Color\Http\Entities\Color;
use Modules\Color\Http\Transformers\ColorResource;

class ColorService
{
    private Color $model;

    /**
     * @param Color $model
     */
    function __construct(Color $model)
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
        $cacheKey = 'colors_list_' . md5(serialize($params));

        $data = Cache::remember($cacheKey, config('cache.color_list_cache_time'), function () use ($params) {
            $query = $this->model->query()->select(['id', 'name', 'hex', 'is_active', 'sort_order']);
            $query = filterLike($query, ['name'], $params);

            if (isset($params['is_active'])) {
                $query->where('is_active', $params['is_active']);
            } else {
                $query->where('is_active', 1);
            }

            return $query->orderBy('sort_order', 'asc')->paginate(20);
        });

        return responseHelper('Colors retrieved successfully.', 200, ColorResource::collection($data));

//        return response()->json([
//            'success' => 200,
//            'message' => __('Colors retrieved successfully.'),
//            'data' => ColorResource::collection($data),
//            'meta' => [
//                'current_page' => $data->currentPage(),
//                'last_page' => $data->lastPage(),
//                'per_page' => $data->perPage(),
//                'total' => $data->total(),
//            ],
//        ]);
    }

    /**
     * Color details
     */
    public function details(int $id): JsonResponse
    {
        try {
            $color = $this->model->findOrFail($id);

            return responseHelper('Colors retrieved successfully.', 200, ColorResource::make($color));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return responseHelper('Colors not found.', 403, []);
        }
    }

    /**
     * Add color
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        $maxSortOrder = $this->model->max('sort_order') ?? 0;
        $validated['sort_order'] = $maxSortOrder + 1;

        $color = handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Color added successfully.',
            ColorResource::class
        );

        Cache::forget('colors_list_' . md5(serialize([])));

        return $color;
    }


    /**
     * Update color
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $color = handleTransaction(
            function () use ($validated, $id) {
                $color = $this->model->findOrFail($id);
                $color->update($validated);
                return $color->refresh();
            },
            'Color updated successfully.',
            ColorResource::class
        );

        Cache::forget('color_list_*');

        return $color;
    }

    /**
     * Delete color
     */
    public function delete(int $id): JsonResponse
    {
        $response = handleTransaction(
            function () use ($id) {
                $color = $this->model->findOrFail($id);
                $color->delete();
                return $color;
            },
            'Color deleted successfully.'
        );

        Cache::forget('color_list_*');

        return $response;
    }
}
