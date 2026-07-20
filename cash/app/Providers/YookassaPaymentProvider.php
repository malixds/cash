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
use Throwable;

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
        $returnUrl = (string) config('services.yookassa.return_url', 'http://localhost/');
        $separator = str_contains($returnUrl, '?') ? '&' : '?';

        $payment = $this->client->createPayment(
            [
                'amount' => [
                    'value' => number_format($order->total, 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => $returnUrl.$separator.http_build_query(['order' => $order->public_id]),
                ],
                'capture' => true,
                'description' => 'Заказ №'.$order->id,
                'metadata' => [
                    'order_id' => $order->id,
                ],
            ],
            (string) $order->public_id,
        );

        return [new PaymentProviderResultDTO(
            providerPaymentId: $payment->getId(),
            paymentUrl: $payment->getConfirmation()->getConfirmationUrl(),
        ), $payment->jsonSerialize()];
    }

    public function verifyWebhook(array $payload, string $signature): bool
    {
        $paymentId = (string) data_get($payload, 'object.id', '');
        $status = (string) data_get($payload, 'object.status', '');
        $amount = number_format((float) data_get($payload, 'object.amount.value', 0), 2, '.', '');

        if ($paymentId === '' || $status === '' || $amount === '0.00') {
            return false;
        }

        try {
            $payment = $this->client->getPaymentInfo($paymentId);

            return hash_equals($paymentId, (string) $payment->getId())
                && hash_equals($status, (string) $payment->getStatus())
                && hash_equals(
                    $amount,
                    number_format((float) $payment->getAmount()->getValue(), 2, '.', ''),
                );
        } catch (Throwable) {
            return false;
        }
    }
}
