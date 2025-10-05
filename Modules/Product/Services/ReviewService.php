<?php

namespace Modules\Product\Services;

use Illuminate\Http\JsonResponse;
use Modules\Product\Http\Entities\Review;
use Modules\Product\Http\Resources\ReviewResource;

class ReviewService
{
    private Review $model;

    /**
     * @param Review $model
     */
    public function __construct(Review $model)
    {
        $this->model = $model;
    }

    /**
     * Review list
     */
    public function list(int $product_id): JsonResponse
    {
        $data = $this->model->with(['user', 'product'])
            ->where('product_id', $product_id)
            ->orderBy('id', 'desc')
            ->paginate(20);

        return responseHelper('Reviews retrieved successfully.', 200, ReviewResource::collection($data));

//        return response()->json([
//            'success' => 200,
//            'message' => __('Reviews retrieved successfully.'),
//            'data' => ReviewResource::collection($data),
//            'meta' => [
//                'current_page' => $data->currentPage(),
//                'last_page' => $data->lastPage(),
//                'per_page' => $data->perPage(),
//                'total' => $data->total(),
//            ],
//        ]);
    }

    /**
     * Add review
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        return handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Review added successfully.',
            ReviewResource::class
        );
    }

    /**
     * Delete review
     */
    public function delete(int $id): JsonResponse
    {
        return handleTransaction(
            function () use ($id) {
                $review = $this->model->findOrFail($id);

                if ($review->user_id !== auth()->id()) {
                    abort(403, 'You are not allowed to delete this review.');
                }

                $review->delete();
                return $review;
            },
            'Review deleted successfully.'
        );
    }
}
