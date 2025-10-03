<?php

namespace Modules\Setting\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Setting\Http\SettingRequest;
use Modules\Setting\Services\SettingService;
use Nwidart\Modules\Facades\Module;

class SettingController extends Controller
{

    private SettingService $service;

    public function __construct(SettingService $service)
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
     * Show the form for editing the specified resource.
     */
    public function update(SettingRequest $request)
    {
        return $this->service->update($request);
    }

}
