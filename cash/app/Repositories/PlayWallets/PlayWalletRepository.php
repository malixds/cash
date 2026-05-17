<?php

namespace App\Repositories\PlayWallets;

use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\DTO\SteamPay\SteamPayCreateResultDTO;
use App\DTO\SteamPay\SteamPayPayResultDTO;
use App\Enums\PlayWalletEnums\ResponseEnum;
use App\Interfaces\PlayWallets\IPlayWalletRepository;
use App\Models\PlayWalletOrder;

class PlayWalletRepository implements IPlayWalletRepository
{
    public function create(PlayWalletCreateDTO $dto): PlayWalletOrder
    {
        return PlayWalletOrder::query()
            ->create([
                'order_id' => $dto->orderId(),
                'status' => ResponseEnum::CREATED->value,
            ]);
    }

    public function firstOrCreate(PlayWalletCreateDTO $dto): PlayWalletOrder
    {
        $playWalletOrder = PlayWalletOrder::query()
            ->where('order_id', $dto->orderId())
            ->first();

        if (!isset($playWalletOrder)) {
            return $this->create($dto);
        }

        return $playWalletOrder;
    }

    public function update(PlayWalletOrder $playWalletOrder, SteamPayCreateResultDTO|SteamPayPayResultDTO $dto): PlayWalletOrder
    {
        $playWalletOrder->update([
            'status' => $dto->getStatus(),
            'play_wallet_uuid' => $dto->getPlayWalletUuid(),
            'payload' => $dto->getPayload(),
            'updated_at' => now(),
        ]);

        return $playWalletOrder->refresh();
    }

    public function findByOrderId(int $orderId): ?PlayWalletOrder
    {
        return PlayWalletOrder::query()->where('order_id', $orderId)->first();
    }
}
