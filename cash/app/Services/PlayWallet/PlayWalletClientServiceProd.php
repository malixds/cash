<?php

namespace App\Services\PlayWallet;

class PlayWalletClientServiceProd extends PlayWalletClientService
{
    protected function baseUrl(): string
    {
        return (string) config('services.playwallet.url_prod');
    }

    protected function apiKey(): string
    {
        return (string) config('services.playwallet.api_key_prod');
    }
}
