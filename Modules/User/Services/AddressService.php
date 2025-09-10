<?php

namespace Modules\User\Services;

use App\Interfaces\ICrudInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\User\Http\Entities\Address;
use Modules\User\Http\Resources\AddressResource;

class AddressService implements ICrudInterface
{

    private Address $model;

    function __construct(Address $model)
    {
        $this->model = $model;
    }

    public function getAll($request): JsonResponse
    {
        $id = auth()->id();
        $data = $this->model->where('user_id', $id)->get();

        return response()->json([
            'success' => 200,
            'message' => __('Brands retrieved successfully.'),
            'data' => AddressResource::collection($data),
        ]);
    }

    public function details(int $id): JsonResponse
    {
        // TODO: Implement details() method.
    }

    /**
     * Add address
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        return handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Address added successfully.',
            AddressResource::class
        );
    }


    public function update(int $id, array $data): JsonResponse
    {
        // TODO: Implement update() method.
    }

    public function delete(int $id): JsonResponse
    {
        // TODO: Implement delete() method.
    }
}
