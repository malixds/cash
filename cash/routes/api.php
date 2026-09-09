<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EnotPaymentWebhookController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\YookassaPaymentPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')->middleware('throttle:30,1')->group(function () {
    Route::post('/create', [OrderController::class, 'createOrder'])->name('order.create');
    Route::get('/{publicId}', [CheckoutController::class, 'show'])
        ->whereUuid('publicId');
});

//Route::prefix('admin')
//    ->middleware('admin.token')
//    ->group(function () {
//        Route::get('/orders', [OrderController::class, 'orderList']);
//        Route::get('/orders/{id}', [OrderController::class, 'orderStatus']);
//    });

Route::post('/payments/enot/webhook', [EnotPaymentWebhookController::class, 'webhook'])
    ->middleware('throttle:120,1')
    ->name('payments.enot.webhook');

Route::post('/payments/webhook', [YookassaPaymentPageController::class, 'webhook'])
    ->middleware('throttle:120,1');
