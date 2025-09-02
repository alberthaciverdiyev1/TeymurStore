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


Route::prefix('product')->controller(\Modules\Product\Http\Controllers\ProductController::class)->group(function () {
    Route::get('/', 'list')->name('product.list');
    Route::get('/{id}', 'details')->name('product.details');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'add')->name('product.add');
        Route::put('/{id}', 'update')->name('product.update');
        Route::delete('/{id}', 'delete')->name('product.delete');
    });
});

