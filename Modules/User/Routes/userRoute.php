<?php

use Modules\User\Http\Controllers\UserController;

Route::controller(UserController::class)->middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::post('/change-email', 'changeEmail')->name('auth.changeEmail');
    Route::get('/list', 'getAll')->name('user.list');
    Route::get('/details/{id?}', 'details')->name('user.details');
});
