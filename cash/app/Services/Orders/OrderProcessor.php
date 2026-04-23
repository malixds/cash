<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Services\PlayWallet\PlayWalletClient;
use Throwable;

class OrderProcessor
{
    public function __construct(
        private readonly PlayWalletClient $playWalletClient,
    ) {
    }

    public function processPaidOrder(Order $order): void
    {
        try {
            $balanceData = $this->playWalletClient->getBalance();
            $serviceId = $balanceData['services'][0]['id'] ?? null;

            if (! is_string($serviceId) || $serviceId === '') {
                throw new \RuntimeException('PlayWallet serviceId is unavailable.');
            }

            $createData = $this->playWalletClient->createOrder(
                externalId: (string) $order->public_id,
                serviceId: $serviceId,
                amount: number_format($order->amount_rub, 2, '.', ''),
                login: $order->steam_login,
            );

            $payData = $this->playWalletClient->payOrder(
                id: (string) ($createData['id'] ?? ''),
                externalId: (string) ($createData['externalId'] ?? $order->public_id),
                createdDateTime: (string) ($createData['createdDateTime'] ?? ''),
            );

            $orderStatus = (string) ($payData['status'] ?? 'error');
            $isCompleted = $orderStatus === 'completed';

            $order->update([
                'status' => $isCompleted ? 'completed' : 'error',
                'playwallet_order_id' => (string) ($payData['id'] ?? $createData['id'] ?? ''),
                'playwallet_status' => $orderStatus,
                'playwallet_payload' => [
                    'create' => $createData,
                    'pay' => $payData,
                ],
                'completed_at' => $isCompleted ? now() : null,
                'error_message' => $isCompleted ? null : 'PlayWallet returned non-completed status: '.$orderStatus,
            ]);
        } catch (Throwable $e) {
            $order->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}

