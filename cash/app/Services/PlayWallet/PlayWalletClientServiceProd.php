<?php

namespace App\Services\PlayWallet;

use App\DTO\SteamPay\SteamPayCreateRequestDTO;
use App\Interfaces\SteamPay\SteamPayClientInterface;
use Illuminate\Support\Facades\Http;

class PlayWalletClientServiceProd implements SteamPayClientInterface
{
    public function pay(SteamPayCreateRequestDTO $requestDTO): SteamPayCreateRequestDTO
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

