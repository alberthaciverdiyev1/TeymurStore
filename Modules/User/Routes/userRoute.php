<?php

use Modules\User\Http\Controllers\UserController;

Route::controller(UserController::class)->middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::put('/change-email', 'changeEmail')->name('auth.changeEmail');
    Route::put('/change-name', 'changeName')->name('auth.changeName');
    Route::put('/change-surname', 'changeSurname')->name('auth.changeSurname');
    Route::put('/change-phone', 'changePhone')->name('auth.changePhone');
    Route::get('/list', 'getAll')->name('user.list');
    Route::get('/details/{id?}', 'details')->name('user.details');
});
