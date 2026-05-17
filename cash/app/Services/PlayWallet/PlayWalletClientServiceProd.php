<?php

namespace App\Services\PlayWallet;

use App\DTO\SteamPay\SteamPayCreateRequestDTO;
use App\DTO\SteamPay\SteamPayCreateResultDTO;
use App\Interfaces\SteamPay\SteamPayClientInterface;
use Illuminate\Support\Facades\Http;

class PlayWalletClientServiceProd implements SteamPayClientInterface
{
    public function createOrder(SteamPayCreateRequestDTO $requestDTO): ?SteamPayCreateResultDTO
    {
        $response = Http::post(
            (string) config('services.playwallet.url_prod', ''),
            [
                'amount' => $requestDTO->amount(),
                'steam_login' => $requestDTO->steamLogin(),
            ]
        );

    }
}

