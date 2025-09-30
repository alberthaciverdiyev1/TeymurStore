<?php

namespace Modules\User\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\User\Http\Entities\OtpEmail;
use Modules\User\Http\Entities\User;
use Modules\User\Http\UserResource;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class UserService
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function changeEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|unique:users,email',
            'otpCode' => 'required|digits:4',
        ]);

        $user = $request->user();

        $otpCheck = OtpEmail::where([
            'email' => $user['email'],
            'otp_code' => $validated['otpCode']
        ])->where('deactive_date', '>', now())->first();

        if (!$otpCheck) {
            return response()->json([
                'status' => StatusCode::HTTP_FORBIDDEN,
                'message' => 'OTP code is invalid or expired'
            ], StatusCode::HTTP_FORBIDDEN);
        }

        return handleTransaction(function () use ($user, $validated, $otpCheck) {
            $user->email = $validated['email'];
            $user->save();

            $otpCheck->delete();

            return $user;
        }, 'Email changed successfully');
    }

    public function changeName(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();


        return handleTransaction(function () use ($user, $validated) {
            $user->name = $validated['name'];
            $user->save();
            return $user;
        }, 'Name changed successfully');
    }

    public function changePhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:255',
        ]);

        $user = $request->user();


        return handleTransaction(function () use ($user, $validated) {
            $user->phone = $validated['phone'];
            $user->save();
            return $user;
        }, 'Phone changed successfully');
    }

    public function changeSurname(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'surname' => 'required|string|max:255',
        ]);

        $user = $request->user();


        return handleTransaction(function () use ($user, $validated) {
            $user->surname = $validated['surname'];
            $user->save();
            return $user;
        }, 'Surname changed successfully');
    }

    public function getAll(): JsonResponse
    {
        $users = User::with('balance')->get();

        return response()->json([
            'success' => 200,
            'user' => UserResource::make($users),
        ]);
    }

    public function details(int $id = null): JsonResponse
    {
        $user = $id ? User::find($id) : Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => UserResource::make($user),
        ]);
    }

}
