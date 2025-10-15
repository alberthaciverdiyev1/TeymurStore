<?php

namespace Modules\Delivery\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Delivery\Http\Requests\DeliveryAddRequest;
use Modules\Delivery\Http\Requests\DeliveryUpdateRequest;
use Modules\Delivery\Services\DeliveryService;

class DeliveryController extends Controller
{

    private DeliveryService $service;

    public function __construct(DeliveryService $service)
    {
        $this->middleware('permission:view deliveries')->only('list');
        $this->middleware('permission:add delivery')->only('add');
        $this->middleware('permission:details delivery')->only('details');
        $this->middleware('permission:update delivery')->only('update');
        $this->middleware('permission:delete delivery')->only('delete');

        $this->service = $service;
    }


    /**
     * Display a listing of the resource.
     */
    public function list(Request $request)
    {
        return $this->service->list($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function add(DeliveryAddRequest $request)
    {
        return $this->service->add($request);
    }

    /**
     * Show the specified resource.
     */
    public function details(int $id)
    {
        return $this->service->details($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(DeliveryUpdateRequest $request, int $id)
    {
        return $this->service->update($request, $id);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function delete(int $id)
    {
        return $this->service->delete($id);
    }
}
