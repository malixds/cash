<?php

namespace App\Services\PlayWallet;

use App\DTO\SteamPay\SteamPayCreateRequestDTO;
use App\DTO\SteamPay\SteamPayCreateResultDTO;
use App\DTO\SteamPay\SteamPayPayResultDTO;
use App\Enums\PlayWalletEnums\ResponseEnum;
use App\Interfaces\SteamPay\SteamPayClientInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PlayWalletClientServiceDev implements SteamPayClientInterface
{
    /**
     * @throws ConnectionException
     */
    public function createOrder(SteamPayCreateRequestDTO $requestDTO): ?SteamPayCreateResultDTO
    {
        $bodyData = [
            'externalId' => $requestDTO->getExternalId(),
            'serviceId' => $requestDTO->getServiceId(),
            'amount' => $requestDTO->getAmount(),
            'login' => $requestDTO->getLogin(),
        ];
        $url = config('services.playwallet.url_dev') . '/create-order';

        $response = Http::withHeaders([
            'pw-api-key' => config('services.playwallet.api_key_dev'),
        ])->post(
            url: $url,
            data: $bodyData,
        );

        return new SteamPayCreateResultDTO(
            status: $response['status'],
            message: $response['message'],
            playWalletUuid: $response['data']['play_wallet_uuid'],
            externalId: $response['data']['externalId'],
            statusOrder: $response['data']['status'],
            payload: $response->body(),
            createdDateTime: $response['data']['createdDateTime'],
        );
    }

    /**
     * @throws ConnectionException
     */
    public function payOrder(SteamPayCreateResultDTO $resultDTO): ?SteamPayPayResultDTO
    {
        $id = $resultDTO->getExternalId();
        $token = hash('sha512', $id . $resultDTO->getCreatedDateTime());
        $bodyData = [
            'id' => $id,
            'externalId' => $resultDTO->getExternalId(),
            'token' => $token,
        ];
        $url = config('services.playwallet.url_dev') . '/pay-order';

        $response = Http::withHeaders([
            'pw-api-key' => config('services.playwallet.api_key_dev'),
        ])->post(
            url: $url,
            data: $bodyData,
        );

        return new SteamPayPayResultDTO(
            status: $response['status'],
            message: $response['message'],
            playWalletUuid: $response['data']['play_wallet_uuid'],
            externalId: $response['data']['externalId'],
            statusOrder: $response['data']['status'],
            payload: $response->body(),
            createdDateTime: $response['data']['createdDateTime'],
        );
    }
}

