<?php

namespace Modules\PromoCode\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PromoCode\Http\Requests\PromoCodeAddRequest;
use Modules\PromoCode\Http\Requests\PromoCodeUpdateRequest;
use Modules\PromoCode\Services\PromoCodeService;
use Nwidart\Modules\Facades\Module;

class PromoCodeController extends Controller
{

    private PromoCodeService $service;

    public function __construct(PromoCodeService $service)
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
    public function getAll(Request $request)
    {
        return $this->service->getAll($request);
    }

    public function check($code)
    {
        return $this->service->check($code);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function add(PromoCodeAddRequest $request)
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
    public function update(PromoCodeUpdateRequest $request, int $id)
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

    public function checkPromoCodeWithPrice(string $code, Request $request)
    {
        return $this->service->checkPromoCodeWithPrice($code, $request);
    }
//    public function checkPromoCodeWithPrice(string $code, Request $request)
//    {
//        return $this->service->checkPromoCodeWithPrice($code, $request, true,);
//    }
}
