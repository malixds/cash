<?php

use App\Http\Controllers\MockPaymentPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home.index');
});

Route::view('/ru', 'pages.home.index');

// Юридические / информационные страницы (единый домен)
Route::view('/oferta', 'pages.legal.oferta')->name('legal.oferta');
Route::view('/privacy', 'pages.legal.privacy')->name('legal.privacy');
Route::view('/refund', 'pages.legal.refund')->name('legal.refund');
Route::view('/delivery', 'pages.legal.delivery')->name('legal.delivery');
Route::view('/contacts', 'pages.legal.contacts')->name('legal.contacts');

if (app()->environment(['local', 'testing'])) {
    Route::get('/mock-provider/payments/{orderPublicId}/{paymentId}', [MockPaymentPageController::class, 'show'])
        ->name('mock.payments.show');
    Route::post('/mock-provider/payments/{orderPublicId}/{paymentId}/complete', [MockPaymentPageController::class, 'complete'])
        ->name('mock.payments.complete');
}

