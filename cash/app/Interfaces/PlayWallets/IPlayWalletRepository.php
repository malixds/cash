<?php

namespace App\Interfaces\PlayWallets;

use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\Models\PlayWalletOrder;

interface IPlayWalletRepository
{
    public function create(PlayWalletCreateDTO $dto): PlayWalletOrder;
}
