<?php

namespace App\DTO\SteamPay;

final readonly class SteamPayCreateRequestDTO
{
    public function __construct(
        private string $externalId,
        private string $serviceId,
        private string $amount,
        private string $login,
    ) {}

    public function getExternalId(): string
    {
        return $this->externalId;
    }
    public function getServiceId(): string
    {
        return $this->serviceId;
    }
    public function getAmount(): string
    {
        return $this->amount;
    }
    public function getLogin(): string
    {
        return $this->login;
    }
}
