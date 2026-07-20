<?php

namespace Tests\Feature;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentsStatusEnum;
use App\Interfaces\Payments\PaymentProviderInterface;
use App\Models\Order;
use App\Models\Payment;
use App\Providers\YookassaPaymentProvider;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;
use YooKassa\Client;
use YooKassa\Model\MonetaryAmount;
use YooKassa\Request\Payments\PaymentResponse;

class PaymentUserFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->fakePlayWalletApi();
    }

    public function test_homepage_and_locale_alias_are_available(): void
    {
        $this->get('/')->assertOk()->assertSee('Пополнение Steam', false);
        $this->get('/ru')->assertOk()->assertSee('topupForm', false);
        $this->get('/up')->assertOk();
    }

    public function test_create_order_validates_required_fields(): void
    {
        $this->postJson('/api/orders/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['login', 'amount', 'payment_method']);
    }

    public function test_user_can_create_order_pay_via_mock_provider_and_get_completed_status(): void
    {
        $create = $this->postJson('/api/orders/create', [
            'login' => 'steam_user_42',
            'amount' => 100,
            'payment_method' => 'sbp',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['data' => ['payment_url', 'order_id']]);

        $orderId = $create->json('data.order_id');
        $paymentUrl = $create->json('data.payment_url');

        $this->assertNotEmpty($orderId);
        $this->assertStringContainsString('/mock-provider/payments/', $paymentUrl);

        $order = Order::query()->where('public_id', $orderId)->firstOrFail();
        $this->assertSame(OrderStatusEnum::NEW->value, $order->status);
        $this->assertSame('steam_user_42', $order->steam_login);
        $this->assertSame(100, $order->amount);
        $this->assertSame(105, $order->total);

        $payment = $order->payment()->firstOrFail();
        $this->assertSame(PaymentsStatusEnum::NEW->value, $payment->status);
        $this->assertSame(105, $payment->amount);

        $this->get($paymentUrl)
            ->assertOk()
            ->assertSee('Мок', false);

        $this->post(route('mock.payments.complete', [
            'orderPublicId' => $order->public_id,
            'paymentId' => $payment->provider_payment_id,
        ]))->assertRedirect('/?order='.$order->public_id);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(OrderStatusEnum::COMPLETED->value, $order->status);
        $this->assertSame(PaymentsStatusEnum::SUCCEEDED->value, $payment->status);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->completed_at);
        $this->assertNotNull($order->playWalletOrder);
        $this->assertSame('completed', $order->playWalletOrder->status);

        $this->getJson('/api/orders/'.$order->public_id)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.status', OrderStatusEnum::COMPLETED->value)
            ->assertJsonPath('data.steam_login', 'steam_user_42');

        Http::assertSent(function (Request $request) use ($order) {
            return str_contains($request->url(), 'create-order')
                && $request['login'] === 'steam_user_42'
                && $request['externalId'] === $order->external_id
                && $request['amount'] === '100.00';
        });

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'pay-order'));
    }

    public function test_yookassa_webhook_fulfills_paid_order_with_real_steam_login(): void
    {
        $this->app->bind(PaymentProviderInterface::class, YookassaPaymentProvider::class);

        $order = Order::query()->create([
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'external_id' => (string) \Illuminate\Support\Str::uuid(),
            'steam_login' => 'real_steam_login',
            'region' => 'ru',
            'amount' => 200,
            'total' => 210,
            'payment_method' => 'card',
            'status' => OrderStatusEnum::NEW->value,
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'provider_payment_id' => 'payment-abc-123',
            'status' => PaymentsStatusEnum::NEW->value,
            'amount' => 210,
            'currency' => 'RUB',
            'payment_url' => 'https://yookassa.test/pay',
        ]);

        $this->mockYookassaPaymentInfo('payment-abc-123', 'succeeded', '210.00');

        $payload = $this->yookassaWebhookPayload('payment-abc-123', 'succeeded', 210.00, true);

        $this->postJson('/api/payments/webhook', $payload)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $order->refresh();
        $this->assertSame(OrderStatusEnum::COMPLETED->value, $order->status);
        $this->assertSame('real_steam_login', $order->steam_login);
        $this->assertNotNull($order->playWalletOrder);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'create-order')
            && $request['login'] === 'real_steam_login');
    }

    public function test_yookassa_webhook_is_idempotent_for_already_processing_order(): void
    {
        $this->app->bind(PaymentProviderInterface::class, YookassaPaymentProvider::class);

        $order = Order::query()->create([
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'external_id' => (string) \Illuminate\Support\Str::uuid(),
            'steam_login' => 'idempotent_user',
            'region' => 'ru',
            'amount' => 50,
            'total' => 53,
            'payment_method' => 'sbp',
            'status' => OrderStatusEnum::PROCESSING->value,
            'paid_at' => now(),
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'provider_payment_id' => 'payment-idempotent',
            'status' => PaymentsStatusEnum::SUCCEEDED->value,
            'amount' => 53,
            'currency' => 'RUB',
            'paid_at' => now(),
        ]);

        $this->mockYookassaPaymentInfo('payment-idempotent', 'succeeded', '53.00');

        $this->postJson('/api/payments/webhook', $this->yookassaWebhookPayload('payment-idempotent', 'succeeded', 53.00, true))
            ->assertOk()
            ->assertJsonPath('message', 'Webhook already processed.');

        Http::assertNothingSent();
    }

    public function test_yookassa_webhook_rejects_unknown_payment_and_amount_mismatch(): void
    {
        $this->app->bind(PaymentProviderInterface::class, YookassaPaymentProvider::class);

        $order = Order::query()->create([
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'external_id' => (string) \Illuminate\Support\Str::uuid(),
            'steam_login' => 'mismatch_user',
            'region' => 'ru',
            'amount' => 100,
            'total' => 105,
            'payment_method' => 'sbp',
            'status' => OrderStatusEnum::NEW->value,
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'provider_payment_id' => 'payment-known',
            'status' => PaymentsStatusEnum::NEW->value,
            'amount' => 105,
            'currency' => 'RUB',
        ]);

        $this->mockYookassaPaymentInfo('payment-missing', 'succeeded', '105.00');
        $this->postJson('/api/payments/webhook', $this->yookassaWebhookPayload('payment-missing', 'succeeded', 105.00, true))
            ->assertNotFound()
            ->assertJsonPath('ok', false);

        $this->mockYookassaPaymentInfo('payment-known', 'succeeded', '999.00');
        $this->postJson('/api/payments/webhook', $this->yookassaWebhookPayload('payment-known', 'succeeded', 999.00, true))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Payment amount mismatch.');
    }

    public function test_yookassa_webhook_rejects_unverified_provider_payload(): void
    {
        $this->app->bind(PaymentProviderInterface::class, YookassaPaymentProvider::class);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('getPaymentInfo')->andThrow(new \RuntimeException('not found'));
        $this->app->instance(Client::class, $client);

        $this->postJson('/api/payments/webhook', $this->yookassaWebhookPayload('payment-x', 'succeeded', 10.00, true))
            ->assertUnauthorized()
            ->assertJsonPath('ok', false);
    }

    public function test_admin_orders_require_token_and_list_local_orders(): void
    {
        $this->getJson('/api/admin/orders')->assertUnauthorized();

        Order::query()->create([
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'external_id' => (string) \Illuminate\Support\Str::uuid(),
            'steam_login' => 'admin_visible',
            'region' => 'ru',
            'amount' => 10,
            'total' => 11,
            'payment_method' => 'sbp',
            'status' => OrderStatusEnum::NEW->value,
        ]);

        $this->getJson('/api/admin/orders', [
            'X-Admin-Token' => 'test-admin-token',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.data.0.steam_login', 'admin_visible');
    }

    public function test_mock_payment_routes_are_available_only_outside_production(): void
    {
        $this->assertTrue(app()->environment('testing'));
        $this->assertNotNull(route('mock.payments.show', [
            'orderPublicId' => (string) \Illuminate\Support\Str::uuid(),
            'paymentId' => (string) \Illuminate\Support\Str::uuid(),
        ], false));
    }

    private function fakePlayWalletApi(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, 'create-order')) {
                return Http::response([
                    'status' => 'success',
                    'message' => 'created',
                    'data' => [
                        'id' => 'pw-'.substr(md5($request['externalId'] ?? 'x'), 0, 12),
                        'externalId' => $request['externalId'] ?? '',
                        'status' => 'created',
                        'createdDateTime' => '2026-07-19T00:00:00Z',
                    ],
                ], 200);
            }

            if (str_contains($url, 'pay-order')) {
                return Http::response([
                    'status' => 'success',
                    'message' => 'paid',
                    'data' => [
                        'id' => $request['id'] ?? 'pw-pay',
                        'externalId' => $request['externalId'] ?? '',
                        'status' => 'completed',
                        'completedDateTime' => '2026-07-19T00:00:01Z',
                    ],
                ], 200);
            }

            return Http::response(['status' => 'error', 'message' => 'unexpected endpoint'], 404);
        });
    }

    private function mockYookassaPaymentInfo(string $paymentId, string $status, string $amount): void
    {
        $amountObject = Mockery::mock(MonetaryAmount::class);
        $amountObject->shouldReceive('getValue')->andReturn($amount);

        $payment = Mockery::mock(PaymentResponse::class);
        $payment->shouldReceive('getId')->andReturn($paymentId);
        $payment->shouldReceive('getStatus')->andReturn($status);
        $payment->shouldReceive('getAmount')->andReturn($amountObject);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('getPaymentInfo')->with($paymentId)->andReturn($payment);
        $this->app->instance(Client::class, $client);
    }

    /**
     * @return array<string, mixed>
     */
    private function yookassaWebhookPayload(string $paymentId, string $status, float $amount, bool $paid): array
    {
        return [
            'type' => 'notification',
            'event' => 'payment.'.$status,
            'object' => [
                'id' => $paymentId,
                'status' => $status,
                'paid' => $paid,
                'amount' => [
                    'value' => number_format($amount, 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'created_at' => '2026-07-19T00:00:00.000Z',
                'captured_at' => $paid ? '2026-07-19T00:00:01.000Z' : null,
            ],
        ];
    }
}
