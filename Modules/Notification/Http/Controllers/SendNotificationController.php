<?php

namespace Modules\Notification\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Http\Requests\NotificationAddRequest;
use Modules\Notification\Services\SendNotificationService;
use Nwidart\Modules\Facades\Module;

class SendNotificationController extends Controller
{
    private SendNotificationService $service;

    public function __construct(SendNotificationService $service)
    {
        // if (Module::find('Roles')->isEnabled()) {
        //     $this->middleware('permission:view notifications')->only('index');
        //     $this->middleware('permission:create notification')->only('create');
        //     $this->middleware('permission:store notification')->only('store');
        //     $this->middleware('permission:edit notification')->only('edit');
        //     $this->middleware('permission:update notification')->only('update');
        //     $this->middleware('permission:destroy notification')->only('destroy');
        // }

        $this->service = $service;
    }

    public function sendNotification(NotificationAddRequest $request)
    {
        return $this->service->sendNotification($request,dryRun: true);
    }

}
