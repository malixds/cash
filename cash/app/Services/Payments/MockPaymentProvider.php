<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProviderInterface
{
    public function createPayment(Order $order): PaymentProviderResult
    {
        $providerPaymentId = (string) Str::uuid();
        $paymentUrl = route('mock.payments.show', [
            'orderPublicId' => $order->public_id,
            'paymentId' => $providerPaymentId,
        ]);

        return new PaymentProviderResult(
            providerPaymentId: $providerPaymentId,
            paymentUrl: $paymentUrl,
            payload: [
                'mock' => true,
            ],
        );
    }

    public function verifyWebhook(array $payload, string $signature): bool
    {
        $secret = (string) env('PAYMENT_WEBHOOK_SECRET', '');
        if ($secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), $secret);

        return hash_equals($expected, $signature);
    }
}

