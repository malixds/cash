<?php

namespace App\Interfaces\SteamPay;

use App\DTO\SteamPay\SteamPayCreateRequestDTO;
use App\DTO\SteamPay\SteamPayCreateResultDTO;
use App\Models\PlayWalletOrder;
use Illuminate\Http\JsonResponse;

interface SteamPayClientInterface
{
    public function pay(SteamPayCreateRequestDTO $requestDTO): SteamPayCreateResultDTO;
}
