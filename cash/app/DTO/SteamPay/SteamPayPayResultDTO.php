<?php

namespace App\DTO\SteamPay;

final readonly class SteamPayPayResultDTO
{
    public function __construct(
        private string $status,
        private string $message,
        private string $playWalletUuid,
        private string $externalId,
        private string $statusOrder,
        private string $payload,
        private string $createdDateTime,
    ) {}

    public function getStatus(): string
    {
        return $this->status;
    }
    public function getMessage(): string
    {
        return $this->message;
    }
    public function getPlayWalletUuid(): string
    {
        return $this->playWalletUuid;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }
    public function getStatusOrder(): string
    {
        return $this->statusOrder;
    }
    public function getPayload(): string
    {
        return $this->payload;
    }
    public function getCreatedDateTime(): string
    {
        return $this->createdDateTime;
    }
}
