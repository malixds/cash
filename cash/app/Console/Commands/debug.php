<?php

namespace App\Console\Commands;

use App\DTO\OrderDTO;
use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentsStatusEnum;
use App\Jobs\PlayWallets\PlayWalletPaymentJob;
use App\Models\Order;
use App\Models\Payment;
use App\Providers\YookassaPaymentProvider;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

#[Signature('app:debug
    {--login=123456789 : Steam login for PlayWallet}
    {--amount=1 : Order amount in RUB}
    {--payment-method=card : Payment method (card|sbp)}
    {--sync : Run PlayWallet job synchronously instead of queue}
    {--fake-yookassa : Skip YooKassa API and use a fake payment id}
    {--only-playwallet= : Skip order/YooKassa, dispatch job for existing order id}')]
#[Description('Create order, simulate paid payment, run PlayWallet job against real API')]
class debug extends Command
{
    public function handle(YookassaPaymentProvider $yookassa): int
    {
        try {
            $onlyPlayWallet = $this->option('only-playwallet');
            if ($onlyPlayWallet !== null && $onlyPlayWallet !== '') {
                return $this->runPlayWalletForExistingOrder((int) $onlyPlayWallet);
            }

            return $this->runFullFlow($yookassa);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->error($e->getFile().':'.$e->getLine());

            return self::FAILURE;
        }
    }

    private function runFullFlow(YookassaPaymentProvider $yookassa): int
    {
        $serviceId = (string) config('services.playwallet.service_id', '');
        if ($serviceId === '') {
            $this->error('PLAYWALLET_SERVICE_ID is not configured.');

            return self::FAILURE;
        }

        $dto = new OrderDTO(
            steamLogin: (string) $this->option('login'),
            amount: max(1, (int) $this->option('amount')),
            promoCode: null,
            paymentMethod: (string) $this->option('payment-method'),
        );

        [$order, $payment, $paymentUrl] = DB::transaction(function () use ($dto, $yookassa): array {
            $order = Order::query()->create([
                ...$dto->toOrderAttributes(),
                'status' => OrderStatusEnum::NEW->value,
            ]);

            if ($this->option('fake-yookassa')) {
                $providerPaymentId = 'debug-'.Str::uuid();
                $paymentUrl = 'https://yookassa.local/debug/'.$providerPaymentId;
                $payload = ['debug' => true];
            } else {
                [$result, $payload] = $yookassa->createPayment($order);
                $providerPaymentId = $result->providerPaymentId;
                $paymentUrl = $result->paymentUrl;
            }

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'provider_payment_id' => $providerPaymentId,
                'status' => PaymentsStatusEnum::NEW->value,
                'amount' => $order->total,
                'currency' => 'RUB',
                'payment_url' => $paymentUrl,
                'provider_payload' => $payload,
            ]);

            return [$order, $payment, $paymentUrl];
        });

        $this->info('Order created');
        $this->table(['Field', 'Value'], [
            ['order_id', $order->id],
            ['public_id', $order->public_id],
            ['external_id', $order->external_id],
            ['steam_login', $order->steam_login],
            ['amount', $order->amount],
            ['total', $order->total],
        ]);

        $this->info('Payment created');
        $this->table(['Field', 'Value'], [
            ['payment_id', $payment->id],
            ['provider_payment_id', $payment->provider_payment_id],
            ['payment_url', $paymentUrl],
        ]);

        if (! $this->option('fake-yookassa')) {
            $this->warn('YooKassa payment was created for real. Prefer --fake-yookassa for PlayWallet-only checks.');
        }

        $this->simulatePaidAndRunPlayWallet($order, $payment);

        return self::SUCCESS;
    }

    private function runPlayWalletForExistingOrder(int $orderId): int
    {
        $order = Order::query()->findOrFail($orderId);
        $payment = $order->payment;

        if ($payment === null) {
            $this->error("Order #{$orderId} has no payment.");

            return self::FAILURE;
        }

        $payment->update([
            'status' => PaymentsStatusEnum::SUCCEEDED->value,
            'paid_at' => now(),
        ]);
        $order->update([
            'status' => OrderStatusEnum::PROCESSING->value,
            'paid_at' => now(),
        ]);

        $this->dispatchPlayWalletJob($order->fresh());

        return self::SUCCESS;
    }

    private function simulatePaidAndRunPlayWallet(Order $order, Payment $payment): void
    {
        $payment->update([
            'status' => PaymentsStatusEnum::SUCCEEDED->value,
            'paid_at' => now(),
        ]);
        $order->update([
            'status' => OrderStatusEnum::PROCESSING->value,
            'paid_at' => now(),
        ]);

        $this->info('Payment marked as succeeded (YooKassa skipped/simulated)');
        $this->dispatchPlayWalletJob($order->fresh());
    }

    private function dispatchPlayWalletJob(Order $order): void
    {
        $playWalletDto = new PlayWalletCreateDTO(
            orderId: $order->id,
            externalOrderId: $order->external_id,
            serviceId: (string) config('services.playwallet.service_id'),
            login: $order->steam_login,
            amount: $order->amount,
        );

        if ($this->option('sync')) {
            PlayWalletPaymentJob::dispatchSync($playWalletDto);
            $this->info('PlayWalletPaymentJob executed synchronously.');
        } else {
            PlayWalletPaymentJob::dispatch($playWalletDto);
            $this->info('PlayWalletPaymentJob dispatched to queue.');
            $this->line('Make sure queue worker is running: docker compose up -d queue');
        }

        $order->refresh()->load('playWalletOrder');

        $this->table(['Field', 'Value'], [
            ['order_status', $order->status],
            ['error_message', $order->error_message ?? '-'],
            ['play_wallet_order_id', $order->playWalletOrder?->id ?? '-'],
            ['play_wallet_uuid', $order->playWalletOrder?->play_wallet_uuid ?? '-'],
            ['play_wallet_status', $order->playWalletOrder?->status ?? '-'],
        ]);

        $this->line('Request logs: play_wallet_request_logs (order_id='.$order->id.')');
    }
}
