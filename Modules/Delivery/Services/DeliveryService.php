<?php

namespace Modules\Delivery\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Delivery\Http\Resources\DeliveryResource;
use Modules\Delivery\Http\Entities\Delivery;

class DeliveryService
{
    private Delivery $model;

    function __construct(Delivery $model)
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
        $cacheKey = 'delivery_list_' . md5(serialize($params));

        $data = Cache::remember($cacheKey, config('cache.delivery_list_cache_time'), function () use ($params) {
            $query = $this->model->query()->select(['id', 'city_name', 'price', 'is_active', 'free_from','delivery_time']);
            $query = filterLike($query, ['city_name'], $params);

            if (isset($params['is_active'])) {
                $query->where('is_active', $params['is_active']);
            } else {
                $query->where('is_active', 1);
            }

            return $query->orderBy('price', 'asc')->paginate(20);
        });

        return response()->json([
            'success' => 200,
            'message' => __('Deliverys retrieved successfully.'),
            'data' => DeliveryResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * Delivery details
     */
    public function details(int $id): JsonResponse
    {
        try {
            $delivery = $this->model->findOrFail($id);

            return response()->json([
                'success' => 200,
                'message' => __('Delivery details retrieved successfully.'),
                'data' => DeliveryResource::make($delivery),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Delivery not found.'),
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

        $delivery = handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Delivery added successfully.',
            DeliveryResource::class
        );

        Cache::forget('delivery_list_' . md5(serialize([])));

        return $delivery;
    }


    /**
     * Update color
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $delivery = handleTransaction(
            function () use ($validated, $id) {
                $delivery = $this->model->findOrFail($id);
                $delivery->update($validated);
                return $delivery->refresh();
            },
            'Delivery updated successfully.',
            DeliveryResource::class
        );

        Cache::forget('delivery_list_*');

        return $delivery;
    }

    /**
     * Delete color
     */
    public function delete(int $id): JsonResponse
    {
        $response = handleTransaction(
            function () use ($id) {
                $delivery = $this->model->findOrFail($id);
                $delivery->delete();
                return $delivery;
            },
            'Delivery deleted successfully.'
        );

        Cache::forget('delivery_list_*');

        return $response;
    }
}
