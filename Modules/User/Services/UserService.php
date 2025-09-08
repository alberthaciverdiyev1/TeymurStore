<?php

namespace Modules\User\Services;

use Illuminate\Http\Request;
use Modules\User\Http\Entities\OtpEmail;
use Modules\User\Http\Entities\User;
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
            'email' => $validated['email'],
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
}
