<?php

namespace App\DTO\Payments;

readonly class PaymentProviderResultDTO
{
    public function __construct(
        public string $providerPaymentId,
        public string $paymentUrl,
    ) {
    }
}

