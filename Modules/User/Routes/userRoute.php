<?php

use Modules\User\Http\Controllers\UserController;

Route::controller(UserController::class)->middleware('auth:sanctum')->group(function () {
    Route::post('change-email', 'changeEmail')->name('auth.changeEmail');
});
