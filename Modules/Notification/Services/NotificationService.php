<?php

namespace Modules\Notification\Services;

use Illuminate\Support\Facades\Http;

class NotificationService
{
    private string $serverKey;

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key');
    }


    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): array
    {
        return $this->sendRequest([
            'to' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data
        ]);
    }


    public function sendToMultiple(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        return $this->sendRequest([
            'registration_ids' => $deviceTokens,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data
        ]);
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
