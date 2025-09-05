<?php

use Illuminate\Http\Request;
use Modules\User\Http\Controllers\AuthController;
use Modules\User\Http\Controllers\FavoritesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


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

Route::prefix('favorite')->middleware('auth:sanctum')->controller(FavoritesController::class)->group(function () {
    Route::get('/', 'list')->name('favorite.list');
    Route::post('/{id}', 'add')->name('favorite.add');
    Route::delete('/{id}', 'delete')->name('favorite.delete');
});


