<?php

namespace App\Interfaces\Payments;

use App\DTO\Payments\PaymentProviderResultDTO;
use App\Models\Order;

interface PaymentProviderInterface
{
    public function createPayment(Order $order): PaymentProviderResultDTO;

    public function verifyWebhook(array $payload, string $signature): bool;
}

