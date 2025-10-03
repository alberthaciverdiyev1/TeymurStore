<?php

Route::prefix('product')->controller(\Modules\Product\Http\Controllers\ProductController::class)->group(function () {
    Route::get('/statistics', 'statistics')->name('product.statistics');
    Route::get('/', 'list')->name('product.list');
    Route::get('/{id}', 'details')->name('product.details');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'add')->name('product.add');
        Route::get('/details/{id}', 'detailsAdmin')->name('product.detailsAdmin');
        Route::put('/{id}', 'update')->name('product.update');
        Route::delete('/{id}', 'delete')->name('product.delete');
    });

});
