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


Route::prefix('brand')->controller(\Modules\Brand\Http\Controllers\BrandController::class)->group(function () {
    Route::get('/', 'list')->name('category.list');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'add')->name('brand.add');
        Route::get('/{id}', 'details')->name('brand.details');
        Route::put('/{id}', 'update')->name('brand.update');
        Route::delete('/{id}', 'delete')->name('brand.delete');
    });
});
