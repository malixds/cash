<?php

namespace App\Jobs\PlayWallets;

use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\DTO\SteamPay\SteamPayCreateRequestDTO;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\PlayWalletEnums\ResponseEnum;
use App\Interfaces\PlayWallets\IPlayWalletRepository;
use App\Interfaces\SteamPay\SteamPayClientInterface;
use App\Models\PlayWalletOrder;
use App\Services\PlayWallet\PlayWalletRequestContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class PlayWalletPaymentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 180];

    public function __construct(
        private readonly PlayWalletCreateDTO $dto,
    ) {
        $this->afterCommit = true;
    }

    public function handle(
        IPlayWalletRepository    $repository,
        SteamPayClientInterface  $steamPayClient,
        PlayWalletRequestContext $requestContext,
    ): void
    {
        $requestContext->bind($this->dto->orderId());

        $playWalletOrder = $repository->firstOrCreate($this->dto);
        $requestContext->setPlayWalletOrderId($playWalletOrder->id);
        $steamPayCreateRequestDTO = new SteamPayCreateRequestDTO(
            externalId: $this->dto->externalOrderId(),
            serviceId: $this->dto->serviceId(),
            amount: $this->dto->amount(),
            login: $this->dto->login(),
        );

        $steamPayCreateResultDTO = $steamPayClient->createOrder(
            requestDTO: $steamPayCreateRequestDTO
        );

        if ($steamPayCreateResultDTO === null
            || $steamPayCreateResultDTO->getStatus() !== ResponseEnum::SUCCESS->value) {
            throw new RuntimeException('PlayWallet did not create the order.');
        }

        $repository->update($playWalletOrder, $steamPayCreateResultDTO);

        if (in_array($steamPayCreateResultDTO->getStatusOrder(), [ResponseEnum::COMPLETED->value], true)) {
            $playWalletOrder->order()->update([
                'status' => OrderStatusEnum::COMPLETED->value,
                'completed_at' => now(),
                'error_message' => null,
            ]);

            return;
        }

        $steamPayPayResultDTO = $steamPayClient->payOrder($steamPayCreateResultDTO);

        if ($steamPayPayResultDTO !== null
            && $steamPayPayResultDTO->getStatus() === ResponseEnum::SUCCESS->value) {
            $repository->update($playWalletOrder, $steamPayPayResultDTO);

            if (in_array($steamPayPayResultDTO->getStatusOrder(), [ResponseEnum::COMPLETED->value], true)) {
                $playWalletOrder->order()->update([
                    'status' => OrderStatusEnum::COMPLETED->value,
                    'completed_at' => now(),
                    'error_message' => null,
                ]);

                return;
            }
        }

        throw new RuntimeException('PlayWallet did not complete the order.');
    }

    public function failed(Throwable $exception): void
    {
        $playWalletOrder = PlayWalletOrder::query()
            ->where('order_id', $this->dto->orderId())
            ->first();

        $playWalletOrder?->update(['status' => ResponseEnum::ERROR->value]);
        $playWalletOrder?->order()->update([
            'status' => OrderStatusEnum::ERROR->value,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
