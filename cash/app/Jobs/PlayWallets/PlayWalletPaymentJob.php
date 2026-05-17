<?php

namespace App\Jobs\PlayWallets;

use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\DTO\SteamPay\SteamPayCreateRequestDTO;
use App\Enums\PlayWalletEnums\ResponseEnum;
use App\Interfaces\PlayWallets\IPlayWalletRepository;
use App\Interfaces\SteamPay\SteamPayClientInterface;
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
    ): void
    {
        $playWalletOrder = $repository->create($this->dto);
        $steamPayCreateRequestDTO = new SteamPayCreateRequestDTO(
            externalId: $this->dto->externalOrderId(),
            serviceId: $this->dto->serviceId(),
            amount: $this->dto->amount(),
            login: $this->dto->login(),
        );

        $steamPayCreateResultDTO = $steamPayClient->createOrder(
            requestDTO: $steamPayCreateRequestDTO
        );
        dd($steamPayCreateResultDTO);
        if ($steamPayCreateResultDTO->getStatus() === ResponseEnum::SUCCESS->value) {
            $repository->update($playWalletOrder, $steamPayCreateResultDTO);
        }

        $steamPayPayResultDTO = $steamPayClient->payOrder(
            $steamPayCreateResultDTO
        );
        dd($steamPayPayResultDTO);
        if ($steamPayPayResultDTO->getStatus() === ResponseEnum::SUCCESS->value) {
            $repository->update($playWalletOrder, $steamPayPayResultDTO);
        }
    }
}
