<?php

namespace App\Repositories\Payments;

use App\Interfaces\Payments\IPaymentRepository;
use App\Models\Payment;

class PaymentRepository implements IPaymentRepository
{

    public function findByPaymentId(string $paymentId): ?Payment
    {
        return Payment::query()->where('provider_payment_id', $paymentId)->first();
    }

    public function update(Payment $payment, array $data): ?Payment
    {
        $payment->update($data);

        return $payment->refresh();
    }
}
