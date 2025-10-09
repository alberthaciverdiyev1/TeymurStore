<?php

use Illuminate\Http\Request;
use Modules\Balance\Http\Controllers\BalanceController;

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

Route::controller(BalanceController::class)
    ->middleware('auth:sanctum')
    ->prefix('balance')
    ->group(function () {
        Route::post('/deposit', 'deposit')->name('balance.deposit');
       // Route::post('/withdraw', 'withdraw')->name('balance.withdraw');
       // Route::get('/total', 'balance')->name('balance.total');
        Route::get('/history', 'getBalanceHistory')->name('balance.history');
    });


