<?php

namespace App\Jobs\PlayWallets;

use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\DTO\SteamPay\SteamPayCreateRequestDTO;
use App\Enums\PlayWalletEnums\ResponseEnum;
use App\Interfaces\PlayWallets\IPlayWalletRepository;
use App\Interfaces\SteamPay\SteamPayClientInterface;
use App\Services\PlayWallet\PlayWalletRequestContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PlayWalletPaymentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PlayWalletCreateDTO $dto,
    ) {}

    public function handle(
        IPlayWalletRepository $repository,
        SteamPayClientInterface $steamPayClient,
        PlayWalletRequestContext $requestContext,
    ): void
    {
        $requestContext->bind($this->dto->orderId());

        $playWalletOrder = $repository->create($this->dto);
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
            return;
        }

        $repository->update($playWalletOrder, $steamPayCreateResultDTO);

        if (in_array($steamPayCreateResultDTO->getStatusOrder(), ['completed', 'paid'], true)) {
            return;
        }

        $steamPayPayResultDTO = $steamPayClient->payOrder($steamPayCreateResultDTO);

        if ($steamPayPayResultDTO !== null
            && $steamPayPayResultDTO->getStatus() === ResponseEnum::SUCCESS->value) {
            $repository->update($playWalletOrder, $steamPayPayResultDTO);
        }
    }
}
