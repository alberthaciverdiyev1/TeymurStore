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


Route::prefix('setting')->controller(\Modules\Setting\Http\Controllers\SettingController::class)->group(function () {
    Route::get('/', 'list')->name('setting.list');
    Route::put('/', 'update')->name('setting.update')->middleware('auth:sanctum');
});
