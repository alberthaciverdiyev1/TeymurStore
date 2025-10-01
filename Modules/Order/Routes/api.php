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


Route::prefix('order')->controller(\Modules\Order\Http\Controllers\OrderController::class)->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', 'getAll')->name('order.list');
        Route::post('/', 'orderFromBasket')->name('order.orderFromBasket');
        Route::post('/{product_id}', 'buyOne')->name('order.buyOne');
        Route::get('/{id}', 'details')->name('order.details');
        Route::put('/{id}', 'update')->name('order.update');
        Route::delete('/{id}', 'delete')->name('order.delete');
    });
});
