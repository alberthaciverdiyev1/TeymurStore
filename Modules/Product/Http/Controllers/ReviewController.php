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
        $this->middleware('permission:view reviews')->only('list');
        $this->middleware('permission:add review')->only('add');
        $this->middleware('permission:delete review')->only('delete');

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
