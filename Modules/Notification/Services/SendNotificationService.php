<?php

namespace Modules\Notification\Services;

use Illuminate\Support\Facades\Http;
use Modules\Notification\Http\Entities\NotificationToken;

class SendNotificationService
{
    private string $serverKey;
    private NotificationToken $tokenModel;
    private NotificationService $notificationService;

    public function __construct(NotificationToken $tokenModel, NotificationService $notificationService)
    {
        $this->serverKey = config('services.fcm.server_key');
        $this->tokenModel = $tokenModel;
        $this->notificationService = $notificationService;
    }

    public function sendToOneUser(string $deviceToken, string $title, string $body, ?string $icon = null, array $data = []): array
    {
        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data
        ];

        if ($icon) {
            $payload['notification']['icon'] = $icon;
        }

        return $this->sendRequest($payload);
    }

    public function sendToMultiple(array $deviceTokens, string $title, string $body, ?string $icon = null, array $data = []): array
    {
        $payload = [
            'registration_ids' => $deviceTokens,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data
        ];

        if ($icon) {
            $payload['notification']['icon'] = $icon;
        }

        return $this->sendRequest($payload);
    }

    public function sendNotification($request)
    {
        $validated = $request->validated();
        $title = $validated['title'] ?? '';
        $body = $validated['body'] ?? '';
        $icon = $validated['icon'] ?? null;
        $data = $validated['data'] ?? [];

        if (empty($validated['users']) || !is_array($validated['users'])) {
            $tokens = $this->tokenModel
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();

            $this->notificationService->addMultiple($validated);

            return $this->sendToMultiple($tokens, $title, $body, $icon, $data);
        }

        $tokens = $this->tokenModel
            ->whereIn('user_id', $validated['users'])
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        $this->notificationService->addMultiple($validated);

        return $this->sendToMultiple($tokens, $title, $body, $icon, $data);
    }

    private function sendRequest(array $payload): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'key=' . $this->serverKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);

        return $response->json();
    }
}
