<?php

namespace App\Providers;

use App\Interfaces\Orders\IOrderRepository;
use App\Interfaces\Payments\IPaymentRepository;
use App\Interfaces\Payments\PaymentProviderInterface;
use App\Interfaces\PlayWallets\IPlayWalletRepository;
use App\Interfaces\SteamPay\SteamPayClientInterface;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\Payments\PaymentRepository;
use App\Repositories\PlayWallets\PlayWalletRepository;
use App\Services\PlayWallet\PlayWalletClientServiceDev;
use App\Services\PlayWallet\PlayWalletClientServiceProd;
use App\Services\PlayWallet\PlayWalletRequestContext;
use App\Services\PlayWallet\PlayWalletRequestLogger;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use YooKassa\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IPaymentRepository::class, PaymentRepository::class);
        $this->app->bind(IOrderRepository::class, OrderRepository::class);
        $this->app->bind(IPlayWalletRepository::class, PlayWalletRepository::class);
        $this->app->scoped(PlayWalletRequestContext::class);
        $this->app->bind(PlayWalletRequestLogger::class);

        $this->app->bind(
            SteamPayClientInterface::class,
            config('services.playwallet.environment') === 'production'
                ? PlayWalletClientServiceProd::class
                : PlayWalletClientServiceDev::class
        );

        $this->app->bind(PaymentProviderInterface::class, function ($app) {
            return match (config('services.payment.provider', 'yookassa')) {
                'mock' => $app->make(MockPaymentProvider::class),
                default => $app->make(YookassaPaymentProvider::class),
            };
        });

        $this->app->singleton(Client::class, function (): Client {
            $shopId = (string) config('services.yookassa.shop_id', '');
            $secretKey = (string) config('services.yookassa.secret_key', '');

            if ($shopId === '' || $secretKey === '') {
                throw new RuntimeException('YooKassa credentials are not configured (YOOKASSA_ID / YOOKASSA_KEY).');
            }

            $client = new Client;
            $client->setAuth($shopId, $secretKey);

            return $client;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
