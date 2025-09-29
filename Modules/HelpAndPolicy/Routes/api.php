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

Route::prefix('faq')->controller(\Modules\HelpAndPolicy\Http\Controllers\FaqController::class)->group(function () {
    Route::get('/', 'getAll')->name('faq.list');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'add')->name('faq.add');
        Route::put('/{id}', 'update')->name('faq.update');
        Route::delete('/{id}', 'delete')->name('faq.delete');
    });

});

Route::prefix('legal-terms')->controller(\Modules\HelpAndPolicy\Http\Controllers\LegalTermController::class)->group(function () {
    Route::get('/{type}', 'details')->name('legal_terms.details');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', 'getAll')->name('legal_terms.list');
        Route::put('/{type}', 'update')->name('legal_terms.update');
    });

});

