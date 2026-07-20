<?php

namespace App\Interfaces\Payments;

use App\Models\Order;

interface PaymentProviderInterface
{
    /**
     * @return array{0: \App\DTO\Payments\PaymentProviderResultDTO, 1: array<string, mixed>}
     */
    public function createPayment(Order $order): array;

    public function verifyWebhook(array $payload, string $signature): bool;
}

