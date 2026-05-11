<?php

namespace App\Providers;

use App\DTO\Payments\PaymentProviderResultDTO;
use App\Interfaces\Payments\PaymentProviderInterface;
use App\Models\Order;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProviderInterface
{
    public function createPayment(Order $order): PaymentProviderResultDTO
    {
        $providerPaymentId = (string) Str::uuid();
        $paymentUrl = route('mock.payments.show', [
            'orderPublicId' => $order->public_id,
            'paymentId' => $providerPaymentId,
        ]);

        return new PaymentProviderResultDTO(
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

