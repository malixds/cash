<?php

use App\Http\Controllers\MockPaymentPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home.index');
});

Route::view('/ru', 'pages.home.index');

if (app()->environment(['local', 'testing'])) {
    Route::get('/mock-provider/payments/{orderPublicId}/{paymentId}', [MockPaymentPageController::class, 'show'])
        ->name('mock.payments.show');
    Route::post('/mock-provider/payments/{orderPublicId}/{paymentId}/complete', [MockPaymentPageController::class, 'complete'])
        ->name('mock.payments.complete');
}

