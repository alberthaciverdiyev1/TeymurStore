<?php

namespace Modules\User\Services;

use Modules\Notification\Http\Entities\NotificationToken;
use Modules\Notification\Services\SendNotificationService;
use Modules\Notification\Services\NotificationTokenService;
use Modules\User\Http\Entities\OtpEmail;
use Modules\User\Http\Entities\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Response;
use Exception;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class AuthService
{
    private User $model;
    private NotificationTokenService $notificationTokenService;

    function __construct(User $model, NotificationTokenService $notificationTokenService)
    {
        $this->model = $model;
        $this->notificationTokenService = $notificationTokenService;
    }

    /**
     * Send OTP
     */
    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email'
        ]);

        $email = $validated['email'];

        // Rate limit (3 OTP)
        if (RateLimiter::tooManyAttempts('send-otp:' . $email, 3)) {
            return response()->json([
                'status' => StatusCode::HTTP_TOO_MANY_REQUESTS,
                'message' => StatusCode::$statusTexts[StatusCode::HTTP_TOO_MANY_REQUESTS]
            ], StatusCode::HTTP_TOO_MANY_REQUESTS);
        }

        $otp = random_int(1000, 9999);
        $deactive_date = now()->addMinutes(10);

        try {
            OtpEmail::updateOrCreate(
                ['email' => $email],
                [
                    'otp_code' => $otp,
                    'deactive_date' => $deactive_date
                ]
            );


            Mail::html(otpMailTemplate($otp), static function ($message) use ($email) {
                $message->to($email)
                    ->subject('OTP Verification');
            });

            RateLimiter::hit('send-otp:' . $email);

            return response()->json([
                'status' => StatusCode::HTTP_CREATED,
                'message' => StatusCode::$statusTexts[StatusCode::HTTP_CREATED],
                'data' => [
                    'deactive_date' => $deactive_date
                ]
            ], StatusCode::HTTP_CREATED);

        } catch (Exception $exception) {
            return response()->json([
                'status' => StatusCode::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $exception->getMessage()
            ], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:4'
        ]);

        $email = $validated['email'];
        $otp   = $validated['otp'];

        try {
            $otpRecord = OtpEmail::where('email', $email)
                ->where('otp_code', $otp)
                ->first();

            if (!$otpRecord) {
                return response()->json([
                    'status'  => StatusCode::HTTP_NOT_FOUND,
                    'message' => 'OTP not found or invalid.'
                ], StatusCode::HTTP_NOT_FOUND);
            }

            if (now()->greaterThan($otpRecord->deactive_date)) {
                return response()->json([
                    'status'  => StatusCode::HTTP_GONE,
                    'message' => 'OTP has expired.'
                ], StatusCode::HTTP_GONE);
            }

            return response()->json([
                'status'  => StatusCode::HTTP_OK,
                'message' => 'OTP verified successfully.'
            ], StatusCode::HTTP_OK);

        } catch (Exception $exception) {
            return response()->json([
                'status'  => StatusCode::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $exception->getMessage()
            ], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Register
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'otpCode' => 'required|digits:4',
//            'device_token' => 'required|string',
//            'device_type' => 'nullable|string'
        ]);

        $email = $validated['email'];
        $otpCode = $validated['otpCode'];

        $otpCheck = OtpEmail::where([
            'email' => $email,
            'otp_code' => $otpCode
        ])
            ->where('deactive_date', '>', now())
            ->first();

        if (!$otpCheck) {
            return response()->json([
                'status' => StatusCode::HTTP_FORBIDDEN,
                'message' => StatusCode::$statusTexts[StatusCode::HTTP_FORBIDDEN]
            ], StatusCode::HTTP_FORBIDDEN);
        }

        $user = $this->model->create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => \now()
        ]);

        $otpCheck->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

       // $notificationTokenResponse = $this->notificationTokenService->updateOrCreate($validated, $user);

        return response()->json([
            'status' => StatusCode::HTTP_CREATED,
            'message' => StatusCode::$statusTexts[StatusCode::HTTP_CREATED],
            'data' => [
                'token' => $token,
                'user' => $user->only(['id', 'name', 'email']),
               // 'notification_data' => $notificationTokenResponse,
            ]
        ], StatusCode::HTTP_CREATED);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
          //  'device_token' => 'required|string',
           // 'device_type' => 'nullable|string'
        ]);

        $user = $this->model->where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => StatusCode::HTTP_UNAUTHORIZED,
                'message' => 'Mail or password incorrect'
            ], StatusCode::HTTP_UNAUTHORIZED);
        }

        if (empty($user->email_verified_at)) {
            return response()->json([
                'status' => StatusCode::HTTP_FORBIDDEN,
                'message' => StatusCode::$statusTexts[StatusCode::HTTP_FORBIDDEN]
            ], StatusCode::HTTP_FORBIDDEN);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        //$notificationTokenResponse = $this->notificationTokenService->updateOrCreate($validated, $user);

        return response()->json([
            'status' => StatusCode::HTTP_OK,
            'message' => StatusCode::$statusTexts[StatusCode::HTTP_OK],
            'data' => [
                'token' => $token,
                'user' => $user->only(['id', 'name', 'email']),
             //   'notification_data' => $notificationTokenResponse,
            ]
        ], StatusCode::HTTP_OK);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        if ($request->device_token) {
            $this->notificationTokenService->deleteToken($request);
        }

        return response()->json([
            'status' => StatusCode::HTTP_OK,
            'message' => StatusCode::$statusTexts[StatusCode::HTTP_OK]
        ], StatusCode::HTTP_OK);
    }

    /**
     * Reset Password
     */
    public function resetPassword(Request $request)
    {

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otpCode' => 'required|digits:4',
            'password' => 'required|string|min:6|confirmed'
        ]);

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

        $user = $this->model->where('email', $validated['email'])->first();
        $user->password = Hash::make($validated['password']);
        $user->save();

        $otpCheck->delete();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => StatusCode::HTTP_OK,
            'message' => "Password reset successfully"
        ], StatusCode::HTTP_OK);
    }

    /**
     * Change Password
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed'
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'status' => StatusCode::HTTP_FORBIDDEN,
                'message' => 'Current password is incorrect'
            ], StatusCode::HTTP_FORBIDDEN);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => StatusCode::HTTP_OK,
            'message' => 'Password changed successfully'
        ], StatusCode::HTTP_OK);
    }

}
