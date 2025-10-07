<?php

namespace Modules\Category\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Category\Http\Requests\CategoryAddRequest;
use Modules\Category\Http\Requests\CategoryUpdateRequest;
use Modules\Category\Services\CategoryService;
use Nwidart\Modules\Facades\Module;

class CategoryController extends Controller
{

    private CategoryService $service;

    public function __construct(CategoryService $service)
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
    public function listWithProducts(Request $request)
    {
        return $this->service->listWithProducts($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function add(CategoryAddRequest $request)
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
    public function update(CategoryUpdateRequest $request, int $id)
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
