<?php

namespace App\DTO\Payments;

final readonly class PaymentProviderWebhookDTO
{
    public function __construct(
        private string $type,
        private string $status,
        private string $id,
        private float $amount,
        private string $created_at,
        private string $captured_at,
        private bool $isPaid,
    ) {}
    public function type(): string
    {
        return $this->type;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function createdAt(): string
    {
        return $this->created_at;
    }

    public function capturedAt(): string
    {
        return $this->captured_at;
    }

    public function isPaid(): bool
    {
        return $this->isPaid;
    }
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'created_at' => $this->created_at,
            'captured_at' => $this->captured_at,
        ];
    }
}
