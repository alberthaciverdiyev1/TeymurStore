<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

Route::prefix('payment')->group(function () {
    Route::get('success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('error', [PaymentController::class, 'error'])->name('payment.error');
    Route::get('result', [PaymentController::class, 'result'])->name('payment.result');
});
