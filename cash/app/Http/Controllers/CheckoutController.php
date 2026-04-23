<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\MockPaymentProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function store(Request $request, MockPaymentProvider $provider): JsonResponse
    {
        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'promo' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['required', 'in:sbp,card'],
        ]);

        $amountRub = (int) $validated['amount'];
        $totalRub = (int) ceil($amountRub * 1.05);

        $order = Order::create([
            'public_id' => (string) Str::uuid(),
            'steam_login' => $validated['nickname'],
            'region' => 'ru',
            'amount_rub' => $amountRub,
            'total_rub' => $totalRub,
            'promo_code' => $validated['promo'] ?? null,
            'payment_method' => $validated['payment_method'],
            'status' => 'pending',
        ]);

        $providerResult = $provider->createPayment($order);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'mock',
            'provider_payment_id' => $providerResult->providerPaymentId,
            'status' => 'pending',
            'amount_rub' => $totalRub,
            'currency' => 'RUB',
            'payment_url' => $providerResult->paymentUrl,
            'provider_payload' => $providerResult->payload,
        ]);

        return response()->json([
            'ok' => true,
            'data' => [
                'order_id' => $order->public_id,
                'status' => $order->status,
                'payment_url' => $providerResult->paymentUrl,
            ],
        ]);
    }

    public function show(string $publicId): JsonResponse
    {
        $order = Order::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        return response()->json([
            'ok' => true,
            'data' => [
                'order_id' => $order->public_id,
                'status' => $order->status,
                'steam_login' => $order->steam_login,
                'amount_rub' => $order->amount_rub,
                'total_rub' => $order->total_rub,
                'error_message' => $order->error_message,
            ],
        ]);
    }
}

