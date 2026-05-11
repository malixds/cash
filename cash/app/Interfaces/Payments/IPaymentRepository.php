<?php

namespace App\Interfaces\Payments;

use App\Models\Payment;
use App\Repositories\Payments\PaymentRepository;

interface IPaymentRepository
{
    public function findByPaymentId(string $paymentId): ?Payment;
    public function update(Payment $payment, array $data): ?Payment;
}
