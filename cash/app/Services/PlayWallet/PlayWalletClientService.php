<?php

namespace App\Services\PlayWallet;

use App\DTO\SteamPay\SteamPayCreateRequestDTO;
use App\DTO\SteamPay\SteamPayCreateResultDTO;
use App\DTO\SteamPay\SteamPayPayResultDTO;
use App\Enums\PlayWalletEnums\ResponseEnum;
use App\Enums\PlayWalletEnums\ResponseMessages;
use App\Interfaces\SteamPay\SteamPayClientInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class PlayWalletClientService implements SteamPayClientInterface
{
    public function __construct(
        private readonly PlayWalletRequestLogger $requestLogger,
    ) {}

    abstract protected function baseUrl(): string;

    abstract protected function apiKey(): string;

    /**
     * @throws ConnectionException
     * @throws PlayWalletException
     */
    public function createOrder(SteamPayCreateRequestDTO $requestDTO): ?SteamPayCreateResultDTO
    {
        $response = $this->post('create-order', [
            'externalId' => $requestDTO->getExternalId(),
            'serviceId' => $requestDTO->getServiceId(),
            'amount' => $requestDTO->getAmount(),
            'login' => $requestDTO->getLogin(),
        ]);

        $message = (string) ($response['message'] ?? '');

        if (ResponseMessages::DUPLICATE->isDuplicateMessage($message, $requestDTO->getExternalId())) {
            return $this->fetchOrderByExternalId($requestDTO->getExternalId());
        }

        return $this->toCreateResultDto($response);
    }

    /**
     * @throws ConnectionException
     * @throws PlayWalletException
     */
    public function payOrder(SteamPayCreateResultDTO $resultDTO): ?SteamPayPayResultDTO
    {
        $orderId = $resultDTO->getPlayWalletUuid();
        $token = hash('sha512', $orderId.$resultDTO->getCreatedDateTime());

        $response = $this->post('pay-order', [
            'id' => $orderId,
            'externalId' => $resultDTO->getExternalId(),
            'token' => $token,
        ]);

        return $this->toPayResultDto($response);
    }

    /**
     * @throws ConnectionException
     * @throws PlayWalletException
     */
    private function fetchOrderByExternalId(string $externalId): SteamPayCreateResultDTO
    {
        $orderData = $this->findOrderDataByExternalId($externalId);

        if ($orderData === null) {
            throw new PlayWalletException("Order not found for externalId: {$externalId}");
        }

        $response = $this->get('get-order/'.$orderData['id']);

        return $this->toCreateResultDto($response);
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     * @throws PlayWalletException
     */
    private function findOrderDataByExternalId(string $externalId): ?array
    {
        $offset = 0;
        $limit = 50;

        do {
            $response = $this->get('get-order-list', [
                'offset' => $offset,
                'limit' => $limit,
            ]);

            $orders = $response['data'] ?? null;

            if (! is_array($orders)) {
                return null;
            }

            foreach ($orders as $order) {
                if (! is_array($order)) {
                    continue;
                }

                if (($order['externalId'] ?? null) === $externalId) {
                    return $order;
                }
            }

            if (count($orders) < $limit) {
                return null;
            }

            $offset += $limit;
        } while ($offset <= 500);

        return null;
    }

    /**
     * @throws ConnectionException
     */
    private function post(string $endpoint, array $body): array
    {
        $url = $this->url($endpoint);
        $httpResponse = $this->http()->post($url, $body);

        $this->requestLogger->log('POST', $endpoint, $url, $body, $httpResponse);

        return $this->decodeResponse($httpResponse, $endpoint);
    }

    /**
     * @throws ConnectionException
     */
    private function get(string $endpoint, array $query = []): array
    {
        $url = $this->url($endpoint);
        $httpResponse = $this->http()->get($url, $query);

        $loggedUrl = $query === []
            ? $url
            : $url.'?'.http_build_query($query);

        $this->requestLogger->log('GET', $endpoint, $loggedUrl, $query === [] ? null : $query, $httpResponse);

        return $this->decodeResponse($httpResponse, $endpoint);
    }

    private function http()
    {
        return Http::withHeaders([
            'pw-api-key' => $this->apiKey(),
        ])->acceptJson()->asJson();
    }

    private function url(string $endpoint): string
    {
        return rtrim($this->baseUrl(), '/').'/'.ltrim($endpoint, '/');
    }

    private function decodeResponse(Response $httpResponse, string $endpoint): array
    {
        $payload = $httpResponse->json();

        if (! is_array($payload)) {
            throw new PlayWalletException(
                sprintf('PlayWallet %s returned a non-JSON response (HTTP %d).', $endpoint, $httpResponse->status()),
            );
        }

        return $payload;
    }

    /**
     * @throws PlayWalletException
     */
    private function toCreateResultDto(array $payload): SteamPayCreateResultDTO
    {
        $status = $payload['status'] ?? null;
        $data = $payload['data'] ?? null;

        if ($status !== ResponseEnum::SUCCESS->value || ! is_array($data)) {
            throw new PlayWalletException(
                (string) ($payload['message'] ?? 'PlayWallet create/get order failed.'),
            );
        }

        $playWalletOrderId = (string) ($data['id'] ?? $data['play_wallet_uuid'] ?? '');

        if ($playWalletOrderId === '') {
            throw new PlayWalletException('PlayWallet response has no order id.');
        }

        return new SteamPayCreateResultDTO(
            status: (string) $status,
            message: (string) ($payload['message'] ?? ''),
            playWalletUuid: $playWalletOrderId,
            externalId: (string) ($data['externalId'] ?? ''),
            statusOrder: (string) ($data['status'] ?? ''),
            payload: json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            createdDateTime: (string) ($data['createdDateTime'] ?? ''),
        );
    }

    /**
     * @throws PlayWalletException
     */
    private function toPayResultDto(array $payload): SteamPayPayResultDTO
    {
        $status = $payload['status'] ?? null;
        $data = $payload['data'] ?? null;

        if ($status !== ResponseEnum::SUCCESS->value || ! is_array($data)) {
            throw new PlayWalletException(
                (string) ($payload['message'] ?? 'PlayWallet pay-order failed.'),
            );
        }

        $playWalletOrderId = (string) ($data['id'] ?? $data['play_wallet_uuid'] ?? '');

        if ($playWalletOrderId === '') {
            throw new PlayWalletException('PlayWallet response has no order id.');
        }

        return new SteamPayPayResultDTO(
            status: (string) $status,
            message: (string) ($payload['message'] ?? ''),
            playWalletUuid: $playWalletOrderId,
            externalId: (string) ($data['externalId'] ?? ''),
            statusOrder: (string) ($data['status'] ?? ''),
            payload: json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            createdDateTime: (string) ($data['createdDateTime'] ?? $data['completedDateTime'] ?? ''),
        );
    }
}
