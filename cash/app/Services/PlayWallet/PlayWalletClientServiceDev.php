<?php

namespace App\Services\PlayWallet;

class PlayWalletClientServiceDev extends PlayWalletClientService
{
    protected function baseUrl(): string
    {
        return (string) config('services.playwallet.url_dev');
    }

    protected function apiKey(): string
    {
        return (string) config('services.playwallet.api_key_dev');
    }
}
