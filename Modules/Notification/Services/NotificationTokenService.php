<?php

namespace Modules\Notification\Services;

use Illuminate\Http\JsonResponse;
use Modules\Notification\Http\Entities\NotificationToken;

class NotificationTokenService
{
    private NotificationToken $model;

    function __construct(NotificationToken $model)
    {
        $this->model = $model;
    }


    public function updateOrCreate($validated, $user): JsonResponse
    {
       return handleTransaction(
           fn() => $this->model->updateOrCreate(
                [
                    'token' => $validated['device_token']
                ],
                [
                    'user_id' => $user->id,
                    'device_type' => $validated['device_type'] ?? 'android',
                    'is_active' => true,
                    'last_used_at' => now()
                ]
            ),
            'Notification token successfully added.',
            null,
            200
        );
    }

    public function deleteToken($request)
    {
        $this->model->where('token', $request->device_token)
            ->update(['is_active' => false]);
    }
}
