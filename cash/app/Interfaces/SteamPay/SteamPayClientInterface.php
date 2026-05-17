<?php

namespace App\Interfaces\SteamPay;

use App\DTO\SteamPay\SteamPayCreateRequestDTO;
use App\DTO\SteamPay\SteamPayCreateResultDTO;
use App\DTO\SteamPay\SteamPayPayResultDTO;
use App\Models\PlayWalletOrder;
use Illuminate\Http\JsonResponse;

interface SteamPayClientInterface
{
    public function createOrder(SteamPayCreateRequestDTO $requestDTO): ?SteamPayCreateResultDTO;

    public function payOrder(SteamPayCreateResultDTO $resultDTO): ?SteamPayPayResultDTO;
}
