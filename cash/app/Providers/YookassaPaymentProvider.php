<?php

namespace App\Providers;

use App\DTO\Payments\PaymentProviderResultDTO;
use App\Interfaces\Payments\PaymentProviderInterface;
use App\Models\Order;
use YooKassa\Client;
use YooKassa\Common\Exceptions\ApiConnectionException;
use YooKassa\Common\Exceptions\ApiException;
use YooKassa\Common\Exceptions\AuthorizeException;
use YooKassa\Common\Exceptions\BadApiRequestException;
use YooKassa\Common\Exceptions\ExtensionNotFoundException;
use YooKassa\Common\Exceptions\ForbiddenException;
use YooKassa\Common\Exceptions\InternalServerError;
use YooKassa\Common\Exceptions\NotFoundException;
use YooKassa\Common\Exceptions\ResponseProcessingException;
use YooKassa\Common\Exceptions\TooManyRequestsException;
use YooKassa\Common\Exceptions\UnauthorizedException;

class YookassaPaymentProvider implements PaymentProviderInterface
{
    public function __construct(
        private readonly Client $client,
    )
    {
    }

    /**
     * @throws NotFoundException
     * @throws ResponseProcessingException
     * @throws ApiException
     * @throws BadApiRequestException
     * @throws ExtensionNotFoundException
     * @throws AuthorizeException
     * @throws InternalServerError
     * @throws ForbiddenException
     * @throws TooManyRequestsException
     * @throws ApiConnectionException
     * @throws UnauthorizedException
     */
    public function createPayment(Order $order): array
    {
        $payment = $this->client->createPayment(
            [
                'amount' => [
                    'value' => number_format($order->total, 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => (string)config('services.yookassa.return_url', 'http://localhost:80/'),
                ],
                'capture' => true, // важно
                'description' => 'Заказ №' . $order->id,

                // можно передать свои данные
                'metadata' => [
                    'order_id' => $order->id,
                ],
            ],
            uniqid('', true)
        );

        return [new PaymentProviderResultDTO(
            providerPaymentId: $payment->getId(),
            paymentUrl: $payment->getConfirmation()->getConfirmationUrl(),
        ), $payment->jsonSerialize()];
    }

    public function verifyWebhook(array $payload, string $signature): bool
    {
        // YooKassa уведомления проверяются иначе (тело запроса / настройки в ЛК). Заглушка до отдельной реализации.
        return false;
    }
}
