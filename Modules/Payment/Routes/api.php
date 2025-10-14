<?php

use Illuminate\Http\Request;

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


Route::controller(\Modules\Notification\Http\Controllers\NotificationController::class)->middleware('auth:sanctum')->prefix('notification')->group(function () {
    Route::get('/', 'getAll')->name('notification.list');
});

