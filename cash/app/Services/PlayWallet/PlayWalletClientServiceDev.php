<?php

namespace App\Services\PlayWallet;

use App\DTO\SteamPay\SteamPayCreateRequestDTO;
use App\DTO\SteamPay\SteamPayCreateResultDTO;
use App\Interfaces\SteamPay\SteamPayClientInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PlayWalletClientServiceDev implements SteamPayClientInterface
{
    /**
     * @throws ConnectionException
     */
    public function pay(SteamPayCreateRequestDTO $requestDTO): SteamPayCreateResultDTO
    {
        dd($requestDTO->getLogin(), config('services.playwallet.url_dev'));
        $response = Http::withHeaders([
            'pw-api-key' => config('services.playwallet.api_key_dev'),
        ])->post(
            config('services.playwallet.url_dev') . '/create-order',
            [
                'externalId' => $requestDTO->getExternalId(),
                'serviceId' => $requestDTO->getServiceId(),
                'amount' => $requestDTO->getAmount(),
                'login' => $requestDTO->getLogin(),
            ]
        );

        dd($response->body());
    }
}

