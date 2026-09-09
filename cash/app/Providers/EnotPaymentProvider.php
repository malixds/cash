<?php

namespace App\Providers;

use App\DTO\Payments\PaymentProviderResultDTO;
use App\Interfaces\Payments\PaymentProviderInterface;
use App\Models\Order;
use Illuminate\Http\Client\Factory as HttpClient;
use RuntimeException;

/**
 * Приём оплаты через Enot (enot.io), метод — СБП.
 *
 * Docs: https://docs.enot.io/e/new/create-invoice, /payment-webhook, /webhook
 */
class EnotPaymentProvider implements PaymentProviderInterface
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    public function createPayment(Order $order): array
    {
        $shopId = (string) config('services.enot.shop_id', '');
        $apiKey = (string) config('services.enot.api_key', '');

        if ($shopId === '' || $apiKey === '') {
            throw new RuntimeException('Enot credentials are not configured (ENOT_SHOP_ID / ENOT_API_KEY).');
        }

        $returnUrl = (string) config('services.enot.return_url', 'http://localhost/');
        $separator = str_contains($returnUrl, '?') ? '&' : '?';
        $redirect = $returnUrl.$separator.http_build_query(['order' => $order->public_id]);
        $sbpService = (string) config('services.enot.sbp_service_code', 'sbp');

        $response = $this->http
            ->acceptJson()
            ->asJson()
            ->withHeaders(['x-api-key' => $apiKey])
            ->post($this->endpoint('invoice/create'), [
                'amount' => number_format((float) $order->total, 2, '.', ''),
                'order_id' => (string) $order->public_id,
                'currency' => 'RUB',
                'shop_id' => $shopId,
                'hook_url' => route('payments.enot.webhook'),
                'success_url' => $redirect,
                'fail_url' => $redirect,
                'comment' => 'Заказ №'.$order->id,
                'expire' => (int) config('services.enot.expire', 300),
                // Ограничиваем форму оплаты только СБП.
                'include_service' => [$sbpService],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Enot invoice creation failed: HTTP '.$response->status().' '.$response->body()
            );
        }

        $data = (array) $response->json('data', []);
        $invoiceId = (string) ($data['id'] ?? '');
        $paymentUrl = (string) ($data['url'] ?? '');

        if ($invoiceId === '' || $paymentUrl === '') {
            throw new RuntimeException('Enot invoice response is missing id/url: '.$response->body());
        }

        return [
            new PaymentProviderResultDTO(
                providerPaymentId: $invoiceId,
                paymentUrl: $paymentUrl,
            ),
            $response->json(),
        ];
    }

    /**
     * Проверка подписи вебхука по официальному алгоритму Enot:
     * ksort() верхнего уровня + json_encode() без флагов + HMAC-SHA256
     * на «Дополнительном ключе» кассы.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhook(array $payload, string $signature): bool
    {
        $secret = (string) config('services.enot.secret_key', '');

        if ($secret === '' || $signature === '') {
            return false;
        }

        ksort($payload);
        $encoded = json_encode($payload);

        if ($encoded === false) {
            return false;
        }

        $expected = hash_hmac('sha256', $encoded, $secret);

        return hash_equals($expected, $signature);
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.enot.url', 'https://api.enot.io'), '/').'/'.ltrim($path, '/');
    }
}
