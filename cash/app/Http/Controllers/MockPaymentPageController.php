<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Providers\MockPaymentProvider;
use App\Services\Orders\OrderProcessor;
use Illuminate\Http\RedirectResponse;

class MockPaymentPageController extends Controller
{
    public function show(string $orderPublicId, string $paymentId)
    {
        $order = Order::query()->where('public_id', $orderPublicId)->firstOrFail();
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('provider_payment_id', $paymentId)
            ->firstOrFail();

        return response()->view('pages.mock-payment', [
            'order' => $order,
            'payment' => $payment,
        ]);
    }

    public function complete(
        string $orderPublicId,
        string $paymentId,
        MockPaymentProvider $provider,
        OrderProcessor $processor,
    ): RedirectResponse {
        $order = Order::query()->where('public_id', $orderPublicId)->firstOrFail();
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('provider_payment_id', $paymentId)
            ->firstOrFail();

        $payload = [
            'payment_id' => $payment->provider_payment_id,
            'status' => 'paid',
            'amount' => $payment->amount,
            'order_id' => $order->public_id,
        ];

        $secret = (string) env('PAYMENT_WEBHOOK_SECRET', '');
        $signature = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), $secret);

        if ($provider->verifyWebhook($payload, $signature)) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'provider_payload' => $payload,
            ]);
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            $processor->processPaidOrder($order);
        }

        return redirect('/?order='.$order->public_id);
    }
}

