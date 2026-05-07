<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderWebhookController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

//Route::post('/checkout', [CheckoutController::class, 'store']);
Route::get('/orders/{publicId}', [CheckoutController::class, 'show']);
Route::post('/webhooks/mock-provider/paid', [OrderWebhookController::class, 'mockPaid']);

Route::prefix('admin')
    ->middleware('admin.token')
    ->group(function () {
        Route::get('/balance', [OrderController::class, 'balance']);
        Route::get('/orders/{id}', [OrderController::class, 'orderStatus']);
        Route::get('/orders', [OrderController::class, 'orderList']);
    });

Route::prefix('orders')->group(function () {
    Route::post('/create', [OrderController::class, 'createOrder'])->name('order.create');
    Route::post('/pay', [OrderController::class, 'createPay']);
    Route::get('/{id}', [OrderController::class, 'show']);
});
