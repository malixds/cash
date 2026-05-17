<?php

namespace App\Interfaces\PlayWallets;

use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\DTO\SteamPay\SteamPayCreateResultDTO;
use App\DTO\SteamPay\SteamPayPayResultDTO;
use App\Models\PlayWalletOrder;

interface IPlayWalletRepository
{
    public function create(PlayWalletCreateDTO $dto): PlayWalletOrder;
    public function firstOrCreate(PlayWalletCreateDTO $dto): PlayWalletOrder;
    public function update(PlayWalletOrder $playWalletOrder, SteamPayCreateResultDTO|SteamPayPayResultDTO $dto): PlayWalletOrder;
    public function findByOrderId(int $orderId): ?PlayWalletOrder;
}
