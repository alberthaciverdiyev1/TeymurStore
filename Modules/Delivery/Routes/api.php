<?php

use Illuminate\Http\Request;
use Modules\Delivery\Http\Controllers\DeliveryController;

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


Route::prefix('delivery')->controller(DeliveryController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'list')->name('delivery.list');
    Route::post('/', 'add')->name('delivery.add');
    Route::get('/{id}', 'details')->name('delivery.details');
    Route::put('/{id}', 'update')->name('delivery.update');
    Route::delete('/{id}', 'delete')->name('delivery.delete');
});



