<?php

namespace App\Services\PlayWallet;

final class PlayWalletRequestContext
{
    private ?int $orderId = null;

    private ?int $playWalletOrderId = null;

    public function bind(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function setPlayWalletOrderId(int $playWalletOrderId): void
    {
        $this->playWalletOrderId = $playWalletOrderId;
    }

    public function orderId(): ?int
    {
        return $this->orderId;
    }

    public function playWalletOrderId(): ?int
    {
        return $this->playWalletOrderId;
    }
}
