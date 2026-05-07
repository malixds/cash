<?php

namespace App\Services\PlayWallet;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PlayWalletClientService
{
    public function getBalance(): array
    {
        return $this->request('get', 'get-balance');
    }

    public function createOrder(
        string $amount,
        string $login,
        ?string $externalId = null,
        ?string $serviceId = null,
    ): array {
        $payload = [
            'amount' => $amount,
            'login' => $login,
        ];

        if ($externalId !== null && $externalId !== '') {
            $payload['externalId'] = $externalId;
        }

        if ($serviceId !== null && $serviceId !== '') {
            $payload['serviceId'] = $serviceId;
        }

        return $this->request('post', 'create-order/', $payload);
    }

    public function payOrder(string $id, string $externalId, string $createdDateTime): array
    {
        return $this->request('post', 'pay-order/', [
            'id' => $id,
            'externalId' => $externalId,
            'token' => hash('sha512', $id.$createdDateTime),
        ]);
    }

    public function getOrder(string $id): array
    {
        return $this->request('get', "get-order/{$id}");
    }

    public function getOrderList(int $offset = 0, int $limit = 10): array
    {
        return $this->request('get', 'get-order-list/', [
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }

    private function request(string $method, string $uri, array $payload = []): array
    {
        $response = $this->client()->{$method}($uri, $payload);

        if (! $response->successful()) {
            throw new PlayWalletException('PlayWallet request failed: '.$response->body());
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new PlayWalletException('PlayWallet returned invalid JSON response.');
        }

        if (($json['status'] ?? null) !== 'success') {
            throw new PlayWalletException((string) ($json['message'] ?? 'PlayWallet API error'));
        }

        return $json['data'] ?? [];
    }

    private function client(): PendingRequest
    {
        $apiKey = (string) config('services.playwallet.api_key');

        if ($apiKey === '') {
            throw new PlayWalletException('PLAYWALLET_API_KEY is not configured.');
        }

        return Http::baseUrl((string) config('services.playwallet.base_url'))
            ->timeout((int) config('services.playwallet.timeout', 15))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'pw-api-key' => $apiKey,
            ]);
    }
}

