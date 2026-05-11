<?php

namespace App\Jobs\PlayWallets;

use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\DTO\SteamPay\SteamPayCreateRequestDTO;
use App\Interfaces\PlayWallets\IPlayWalletRepository;
use App\Interfaces\SteamPay\SteamPayClientInterface;
use App\Models\Order;
use App\Models\PlayWalletOrder;
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
        $steamPayCreateResultDTO = $steamPayClient->pay(
            requestDTO: $steamPayCreateRequestDTO
        );
    }
}
