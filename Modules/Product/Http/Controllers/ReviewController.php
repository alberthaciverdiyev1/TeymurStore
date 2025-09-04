<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Http\Requests\ReviewAddRequest;
use Modules\Product\Services\ReviewService;

class ReviewController extends Controller
{

    private ReviewService $service;

    public function __construct(ReviewService $service)
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
    public function list(int $product_id)
    {
        return $this->service->list($product_id);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function add(ReviewAddRequest $request)
    {
        return $this->service->add($request);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(int $id)
    {
        return $this->service->delete($id);
    }
}
