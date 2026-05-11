<?php

namespace App\Repositories\PlayWallets;

use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\Interfaces\PlayWallets\IPlayWalletRepository;
use App\Models\PlayWalletOrder;

class PlayWalletRepository implements IPlayWalletRepository
{
    public function create(PlayWalletCreateDTO $dto): PlayWalletOrder
    {
        return PlayWalletOrder::query()->create([
            'order_id' => $dto->orderId(),
        ]);
    }
}
