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
    {--only-playwallet : Skip order/YooKassa, dispatch job for existing order id}')]
#[Description('Create order + YooKassa payment, simulate succeeded webhook, run PlayWallet job')]
class debug extends Command
{
    public function handle(YookassaPaymentProvider $yookassa): int
    {
        try {
            if ($orderId = $this->option('only-playwallet')) {
                return $this->runPlayWalletForExistingOrder((int) $orderId);
            }

            return $this->runFullFlow($yookassa);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function runFullFlow(YookassaPaymentProvider $yookassa): int
    {
        $dto = new OrderDTO(
            steamLogin: (string) $this->option('login'),
            amount: max(1, (int) $this->option('amount')),
            promoCode: null,
            paymentMethod: (string) $this->option('payment-method'),
        );

        [$order, $payment, $paymentUrl] = DB::transaction(function () use ($dto, $yookassa): array {
            $order = Order::query()->create([
                'status' => OrderStatusEnum::NEW->value,
                ...$dto->toOrderAttributes(),
            ]);

            if ($this->option('fake-yookassa')) {
                $providerPaymentId = 'debug-' . Str::uuid();
                $paymentUrl = 'https://yookassa.local/debug/' . $providerPaymentId;
            } else {
                $result = $yookassa->createPayment($order);
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

        $this->info('YooKassa payment created');
        $this->table(['Field', 'Value'], [
            ['payment_id', $payment->id],
            ['provider_payment_id', $payment->provider_payment_id],
            ['payment_url', $paymentUrl],
        ]);

        if (! $this->option('fake-yookassa')) {
            $this->warn('Pay at the URL above, or re-run with --fake-yookassa to skip YooKassa.');
        }

        $this->simulateYookassaSucceeded($order, $payment);

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

        $payment->update(['status' => PaymentsStatusEnum::SUCCEEDED->value]);
        $order->update(['status' => OrderStatusEnum::PAID->value]);

        $this->dispatchPlayWalletJob($order);

        return self::SUCCESS;
    }

    private function simulateYookassaSucceeded(Order $order, Payment $payment): void
    {
        $payment->update(['status' => PaymentsStatusEnum::SUCCEEDED->value]);
        $order->update(['status' => OrderStatusEnum::PAID->value]);

        $this->info('Payment marked as succeeded (YooKassa webhook simulated)');

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
            $this->line('Run: php artisan queue:work');
        }
    }
}
