<?php

namespace Modules\Notification\Services;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\Notification\Http\Entities\NotificationToken;
use Modules\Notification\Services\NotificationService;

class SendNotificationService
{
    private string $projectId;
    private string $serviceAccountPath;
    private NotificationToken $tokenModel;
    private NotificationService $notificationService;

    public function __construct(NotificationToken $tokenModel, NotificationService $notificationService)
    {
        $this->projectId = config('services.fcm.project_id');
        $this->serviceAccountPath = storage_path(config('services.fcm.service_account_path'));
        $this->tokenModel = $tokenModel;
        $this->notificationService = $notificationService;
    }

    public function sendToOneUser(string $deviceToken, string $title, string $body, ?string $icon = null, array $data = [], bool $dryRun = false): array
    {
        $formattedData = $this->convertDataToStrings($data);

        $message = [
            'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];

        if ($icon) {
            $message['notification']['image'] = $icon;
        }

        if (!empty($formattedData)) {
            $message['data'] = $formattedData;
        }

        if ($dryRun) {
            return $this->sendV1Request(['message' => $message, 'validate_only' => true]);
        }

        return $this->sendV1Request(['message' => $message]);
    }

    public function sendToMultiple(array $deviceTokens, string $title, string $body, ?string $icon = null, array $data = [], bool $dryRun = false): array
    {
        if (empty($deviceTokens)) {
            return ['success' => 0, 'failure' => 0, 'results' => []];
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;
        $tokensToDelete = [];

        foreach ($deviceTokens as $token) {
            if (empty($token)) continue;

            $result = $this->sendToOneUser($token, $title, $body, $icon, $data, $dryRun);

            if (isset($result['name']) && !isset($result['error'])) {
                $successCount++;
            } else {
                $failureCount++;
                if (!$dryRun && isset($result['error']['status']) && in_array($result['error']['status'], ['INVALID_ARGUMENT', 'UNREGISTERED'])) {
                    $tokensToDelete[] = $token;
                    Log::info('Invalid FCM token found, scheduled for deletion.', ['token' => $token]);
                }
            }

            $results[] = $result;
        }

        if (!$dryRun && !empty($tokensToDelete)) {
            $this->tokenModel->whereIn('token', $tokensToDelete)->delete();
            Log::info('Deleted ' . count($tokensToDelete) . ' invalid FCM tokens from the database.');
        }

        return [
            'success' => $successCount,
            'failure' => $failureCount,
            'results' => $results
        ];
    }

    public function sendNotification($request, bool $dryRun = true)
    {
        try {
            $validated = $request->validated();
            $title = $validated['title'] ?? '';
            $body = $validated['body'] ?? '';
            $icon = $validated['icon'] ?? null;
            $data = $validated['data'] ?? [];

            if (empty($validated['users']) || !is_array($validated['users'])) {
                $userData = $this->tokenModel
                    ->select('user_id', 'token')
                    ->where('is_active', true)
                    ->get();

                if ($userData->isEmpty()) {
                    return ['error' => 'No active users found'];
                }

                $tokens = $userData->pluck('token')->filter()->toArray();
                $userIds = $userData->pluck('user_id')->toArray();

                $this->notificationService->addMultiple($validated, $userIds);

                return $this->sendToMultiple($tokens, $title, $body, $icon, $data, $dryRun);
            }

            $tokens = $this->tokenModel
                ->whereIn('user_id', $validated['users'])
                ->where('is_active', true)
                ->pluck('token')
                ->filter()
                ->toArray();

            if (empty($tokens)) {
                return ['error' => 'No active tokens found for specified users'];
            }

            $this->notificationService->addMultiple($validated, $validated['users']);

            return $this->sendToMultiple($tokens, $title, $body, $icon, $data, $dryRun);

        } catch (\Exception $e) {
            Log::error('SendNotification error: ' . $e->getMessage());
            return ['error' => 'Failed to send notification: ' . $e->getMessage()];
        }
    }

    private function sendV1Request(array $payload): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if (!$response->successful()) {
                Log::error('FCM V1 request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $url,
                    'payload' => $payload
                ]);
            }

            return $response->json() ?? ['error' => 'Invalid response from FCM'];

        } catch (\Exception $e) {
            Log::error('FCM V1 send request exception: ' . $e->getMessage());
            return ['error' => 'Failed to send FCM request: ' . $e->getMessage()];
        }
    }

    private function getAccessToken(): string
    {
        return Cache::remember('fcm_access_token', 55 * 60, function () {
            try {
                $client = new GoogleClient();
                $client->setAuthConfig($this->serviceAccountPath);
                $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

                $accessToken = $client->fetchAccessTokenWithAssertion();

                if (isset($accessToken['error'])) {
                    throw new \Exception('Failed to get access token: ' . $accessToken['error']);
                }

                return $accessToken['access_token'];

            } catch (\Exception $e) {
                Log::error('Failed to get FCM access token: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    private function convertDataToStrings(array $data): array
    {
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[$key] = is_string($value) ? $value : json_encode($value);
        }
        return $stringData;
    }
}
