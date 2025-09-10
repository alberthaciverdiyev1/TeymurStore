<?php

namespace Modules\User\Http\Controllers;

use App\Interfaces\ICrudInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Modules\User\Http\AddressAddRequest;
use Modules\User\Services\AddressService;

class AddressController
{
    private AddressService $service;

    public function __construct(AddressService $service)
    {
        $this->service = $service;
    }

    public function getAll(Request $request): JsonResponse
    {
        return $this->service->getAll($request);
    }

    public function details(int $id): JsonResponse
    {
        return $this->service->details($id);
    }

    public function add(AddressAddRequest $request): JsonResponse
    {
        return $this->service->add($request);
    }

    public function update(int $id, array $data): JsonResponse
    {
        return $this->service->update($id, $data);
    }

    public function delete(int $id): JsonResponse
    {
        return $this->service->delete($id);
    }
}
