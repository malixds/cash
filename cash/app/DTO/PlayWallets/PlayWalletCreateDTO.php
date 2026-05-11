<?php

namespace App\DTO\PlayWallets;

final readonly class PlayWalletCreateDTO
{

    public function __construct(
        private int $orderId,
        private string $externalOrderId,
        private string $serviceId,
        private string $login,
        private string $amount,
    ) {}

    public function orderId(): int
    {
        return $this->orderId;
    }
    public function login(): string
    {
        return $this->login;
    }
    public function amount(): string
    {
        return $this->amount;
    }
    public function serviceId(): string
    {
        return $this->serviceId;
    }
    public function externalOrderId(): string
    {
        return $this->externalOrderId;
    }
}
