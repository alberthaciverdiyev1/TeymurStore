<?php

namespace Modules\Brand\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Brand\Http\Requests\BrandAddRequest;
use Modules\Brand\Http\Requests\BrandUpdateRequest;
use Modules\Brand\Services\BrandService;
use Nwidart\Modules\Facades\Module;

class BrandController extends Controller
{

    private BrandService $service;

    public function __construct(BrandService $service)
    {
//        if (Module::find('Roles')->isEnabled()) {
//            $this->middleware('permission:view categorys')->only('index');
//            $this->middleware('permission:create category')->only('create');
//            $this->middleware('permission:store category')->only('store');
//            $this->middleware('permission:edit category')->only('edit');
//            $this->middleware('permission:update category')->only('update');
//            $this->middleware('permission:destroy category')->only('destroy');
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
    public function add(BrandAddRequest $request)
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
    public function update(BrandUpdateRequest $request, int $id)
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
