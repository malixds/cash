<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PlayWalletController;
use Illuminate\Support\Facades\Route;

Route::post('/checkout', [CheckoutController::class, 'store']);
Route::get('/orders/{publicId}', [CheckoutController::class, 'show']);
Route::post('/webhooks/mock-provider/paid', [PaymentWebhookController::class, 'mockPaid']);

Route::prefix('admin/playwallet')
    ->middleware('admin.token')
    ->group(function () {
        Route::get('/balance', [PlayWalletController::class, 'balance']);
        Route::get('/orders/{id}', [PlayWalletController::class, 'orderStatus']);
        Route::get('/orders', [PlayWalletController::class, 'orderList']);
        Route::post('/orders/create', [PlayWalletController::class, 'createOrder']);
        Route::post('/orders/pay', [PlayWalletController::class, 'payOrder']);
    });