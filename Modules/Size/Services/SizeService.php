<?php

namespace Modules\Size\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\Size\Http\Entities\Size;
use Modules\Size\Http\Transformers\SizeResource;

class SizeService
{
    private Size $model;

    /**
     * @param Size $model
     */
    function __construct(Size $model)
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
        $cacheKey = 'size_list_' . md5(serialize($params));

        $data = Cache::remember($cacheKey, config('cache.size_list_cache_time'), function () use ($params) {
            $query = $this->model->query()->select(['id', 'name', 'icon', 'is_active', 'sort_order']);
            $query = filterLike($query, ['name'], $params);

            if (isset($params['is_active'])) {
                $query->where('is_active', $params['is_active']);
            } else {
                $query->where('is_active', 1);
            }

            return $query->orderBy('sort_order', 'asc')->paginate(20);
        });

        return response()->json([
            'success' => 200,
            'message' => __('Sizes retrieved successfully.'),
            'data' => SizeResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * Size details
     */
    public function details(int $id): JsonResponse
    {
        try {
            $color = $this->model->findOrFail($id);

            return response()->json([
                'success' => 200,
                'message' => __('Size details retrieved successfully.'),
                'data' => SizeResource::make($color),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Size not found.'),
                'data' => [],
            ]);
        }
    }

    /**
     * Add color
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('icon')) {
            $icon = $request->file('icon');
            $iconName = time() . '_' . $icon->getClientOriginalName();

            if (!Storage::disk('public')->exists('brands')) {
                Storage::disk('public')->makeDirectory('brands', 0755, true);
            }

            $icon->storeAs('sizes', $iconName, 'public');
            $validated['icon'] = 'sizes/' . $iconName;
        }
        $maxSortOrder = $this->model->max('sort_order') ?? 0;
        $validated['sort_order'] = $maxSortOrder + 1;

        $color = handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Size added successfully.',
            SizeResource::class
        );

        Cache::forget('size_list_' . md5(serialize([])));

        return $color;
    }


    /**
     * Update color
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('icon')) {
            $icon = $request->file('icon');
            $iconName = time() . '_' . $icon->getClientOriginalName();

            if (!Storage::disk('public')->exists('brands')) {
                Storage::disk('public')->makeDirectory('brands', 0755, true);
            }

            $icon->storeAs('sizes', $iconName, 'public');
            $validated['icon'] = 'sizes/' . $iconName;
        }
        $color = handleTransaction(
            function () use ($validated, $id) {
                $color = $this->model->findOrFail($id);
                $color->update($validated);
                return $color->refresh();
            },
            'Size updated successfully.',
            SizeResource::class
        );

        Cache::forget('size_list_*');

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
            'Size deleted successfully.'
        );

        Cache::forget('size_list_*');

        return $response;
    }
}
