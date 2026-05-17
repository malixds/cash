<?php

namespace App\Services\PlayWallet;

use App\Models\PlayWalletRequestLog;
use Illuminate\Http\Client\Response;

class PlayWalletRequestLogger
{
    public function __construct(
        private readonly PlayWalletRequestContext $context,
    ) {}

    /**
     * @param  array<string, mixed>|null  $requestBody
     */
    public function log(
        string $method,
        string $endpoint,
        string $url,
        ?array $requestBody,
        Response $httpResponse,
    ): void {
        $payload = $httpResponse->json();

        PlayWalletRequestLog::query()->create([
            'order_id' => $this->context->orderId(),
            'play_wallet_order_id' => $this->context->playWalletOrderId(),
            'method' => strtoupper($method),
            'endpoint' => $endpoint,
            'url' => $url,
            'request_body' => $requestBody,
            'response_body' => $this->normalizeResponseBody($httpResponse, $payload),
            'http_status' => $httpResponse->status(),
            'api_status' => is_array($payload) ? ($payload['status'] ?? null) : null,
            'api_message' => is_array($payload) ? ($payload['message'] ?? null) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeResponseBody(Response $httpResponse, mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        return [
            'raw' => $httpResponse->body(),
        ];
    }
}
