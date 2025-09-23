<?php

use Illuminate\Http\Request;
use Modules\Notification\Http\Controllers\SendNotificationController;

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



Route::controller(SendNotificationController::class)->middleware('auth:sanctum')->prefix('notification')->group(function () {
    Route::post('/', 'sendNotification')->name('notification.send');
});
Route::controller(\Modules\Notification\Http\Controllers\NotificationTokenController::class)->middleware('auth:sanctum')->prefix('notification')->group(function () {
    Route::post('/save-token', 'saveToken')->name('notification.save-token');
});
