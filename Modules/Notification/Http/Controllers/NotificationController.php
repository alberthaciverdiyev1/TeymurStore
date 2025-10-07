<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Services\NotificationService;

class NotificationController extends Controller
{
    private NotificationService $service;

    function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function getAll(Request $request)
    {
        return $this->service->list($request);
    }
}
