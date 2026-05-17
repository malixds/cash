<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderWebhookController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\YookassaPaymentPageController;
use Illuminate\Support\Facades\Route;

//Route::post('/checkout', [CheckoutController::class, 'store']);
Route::post('/webhooks/mock-provider/paid', [OrderWebhookController::class, 'mockPaid']);

Route::prefix('orders')->group(function () {
    Route::post('/create', [OrderController::class, 'createOrder'])->name('order.create');
    Route::post('/pay', [OrderController::class, 'createPay']);
    Route::get('/{publicId}', [CheckoutController::class, 'show'])
        ->whereUuid('publicId');
});

Route::prefix('admin')
    ->middleware('admin.token')
    ->group(function () {
        Route::get('/balance', [OrderController::class, 'balance']);
        Route::get('/orders', [OrderController::class, 'orderList']);
        Route::get('/orders/{id}', [OrderController::class, 'orderStatus']);
    });

Route::prefix('payments')->group(function () {
    Route::get('/status', [YookassaPaymentPageController::class, 'getStatus']);
    Route::post('/webhook', [YookassaPaymentPageController::class, 'webhook']);
});
