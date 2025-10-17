<?php

namespace Modules\User\Services;

use Google_Client;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Modules\User\Http\Entities\User;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class GoogleAuthService
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();
        $password = bcrypt(\Str::random(16));

        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'password' => $password
            ]
        );

        $token = $user->createToken('google-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function loginWithToken($request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $idToken = $request->token;

        $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            return response()->json(['error' => __('Invalid Google ID Token')], 403);
        }

        $password = bcrypt(Str::random(16));

        $userData = [
            'name' => $payload['name'] ?? 'Google User',
            'email_verified_at' => now(),
            'email' => $payload['email'],
            'password' => $password,
            'phone' => $payload['phone_number'] ?? null,
        ];

        $user = User::firstOrCreate(
            ['email' => $payload['email']],
            $userData
        );

        $apiToken = $user->createToken('google-token')->plainTextToken;

        return response()->json([
            'status' => StatusCode::HTTP_OK,
            'message' => __(StatusCode::$statusTexts[StatusCode::HTTP_OK]),
            'data' => [
                'token' => $apiToken,
                'user' => $user->only(['id', 'name', 'email']),
            ]
        ], StatusCode::HTTP_OK);

    }
}
