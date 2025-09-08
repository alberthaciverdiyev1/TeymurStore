<?php

use Modules\User\Http\Controllers\AuthController;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register')->name('auth.register');
    Route::post('login', 'login')->name('auth.login');
    Route::post('send-otp', 'sendOtp')->name('auth.sendOtp');
    Route::post('reset-password', 'resetPassword')->name('auth.resetPassword');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', 'logout')->name('auth.logout');
        Route::post('change-password', 'changePassword')->name('auth.changePassword');
    });
});
