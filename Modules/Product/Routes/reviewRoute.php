<?php

use Illuminate\Http\Request;


Route::prefix('review')->middleware('auth:sanctum')->controller(\Modules\Product\Http\Controllers\ReviewController::class)->group(function () {
    Route::get('/{product_id}', 'list')->name('review.list');
    Route::post('/', 'add')->name('review.add');
    Route::delete('/{id}', 'delete')->name('review.delete');
});

