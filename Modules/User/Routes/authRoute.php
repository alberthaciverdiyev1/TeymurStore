<?php

use Modules\User\Http\Controllers\AuthController;
use Modules\User\Http\Controllers\GoogleController;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register')->name('auth.register');
    Route::post('login', 'login')->name('auth.login');
    Route::post('send-otp', 'sendOtp')->name('auth.sendOtp');
    Route::post('check-otp', 'checkOtp')->name('auth.checkOtp');
    Route::post('reset-password', 'resetPassword')->name('auth.resetPassword');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', 'logout')->name('auth.logout');
        Route::post('change-password', 'changePassword')->name('auth.changePassword');
    });
});
Route::prefix('login/google')->controller(GoogleController::class)->group(function () {
    Route::get('/', 'redirectToGoogle')->name('login.google');
    Route::get('/callback', 'handleGoogleCallback');
    Route::post('/with-token', 'loginWithToken')->name('login.google.withToken');
});
