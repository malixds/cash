<?php

namespace App\Services\Payments;

class PaymentProviderResult
{
    public function __construct(
        public readonly string $providerPaymentId,
        public readonly string $paymentUrl,
        public readonly array $payload = [],
    ) {
    }
}

