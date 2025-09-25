<?php

namespace Modules\User\Services;

use App\Interfaces\ICrudInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
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
        $id = auth()->id();
        $data = $this->model->with('product')->where('user_id', $id)->get();

        return response()->json([
            'success' => 200,
            'message' => __('Basket data retrieved successfully.'),
            'data' => BasketResource::collection($data),
        ]);
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

            return handleTransaction(
                fn() => tap($basket)->update($data)->refresh(),
                'Basket updated successfully.',
                BasketResource::class
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Basket not found.'),
            ], 404);
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
            return response()->json([
                'success' => 404,
                'message' => __('Basket not found.'),
            ], 404);
        }
    }
}
