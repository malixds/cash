<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentProviderInterface
{
    public function createPayment(Order $order): PaymentProviderResult;

    public function verifyWebhook(array $payload, string $signature): bool;
}

