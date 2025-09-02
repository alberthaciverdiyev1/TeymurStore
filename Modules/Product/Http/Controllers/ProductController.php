<?php

namespace Modules\Product\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Http\Requests\ProductAddRequest;
use Modules\Product\Http\Requests\ProductUpdateRequest;
use Modules\Product\Services\ProductService;
use Nwidart\Modules\Facades\Module;

class ProductController extends Controller
{

    private ProductService $service;

    public function __construct(ProductService $service)
    {
//        if (Module::find('Roles')->isEnabled()) {
//            $this->middleware('permission:view colors')->only('index');
//            $this->middleware('permission:create color')->only('create');
//            $this->middleware('permission:store color')->only('store');
//            $this->middleware('permission:edit color')->only('edit');
//            $this->middleware('permission:update color')->only('update');
//            $this->middleware('permission:destroy color')->only('destroy');
//        }

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
    public function add(ProductAddRequest $request)
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
    public function update(ProductUpdateRequest $request, int $id)
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
