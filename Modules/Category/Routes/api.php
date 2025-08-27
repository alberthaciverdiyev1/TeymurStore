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


Route::prefix('category')->controller(\Modules\Category\Http\Controllers\CategoryController::class)->group(function () {
    Route::get('/', 'list')->name('category.list');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'add')->name('category.add');
        Route::get('/{id}', 'details')->name('category.details');
        Route::put('/{id}', 'update')->name('category.update');
        Route::delete('/{id}', 'delete')->name('category.delete');
    });
});

