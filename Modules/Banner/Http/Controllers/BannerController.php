<?php

namespace Modules\Banner\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Banner\Services\BannerService;
use Nwidart\Modules\Facades\Module;

class BannerController extends Controller
{

    private BannerService $bannerService;

    public function __construct(BannerService $bannerService)
    {
//        if (Module::find('Roles')->isEnabled()) {
//            $this->middleware('permission:view banners')->only('index');
//            $this->middleware('permission:create banner')->only('create');
//            $this->middleware('permission:store banner')->only('store');
//            $this->middleware('permission:edit banner')->only('edit');
//            $this->middleware('permission:update banner')->only('update');
//            $this->middleware('permission:destroy banner')->only('destroy');
//        }

        $this->bannerService = $bannerService;
    }


    public function getAll(Request $request)
    {
        return $this->bannerService->getAll($request);
    }
    public function add(Request $request)
    {
        return $this->bannerService->add($request);
    }
    public function delete($id)
    {
        return $this->bannerService->delete($id);
    }
}
