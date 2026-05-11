<?php

namespace App\Repositories\Orders;

use App\Interfaces\Orders\IOrderRepository;
use App\Models\Order;

class OrderRepository implements IOrderRepository
{
    public function find(int $id): ?Order
    {
        return Order::query()->find($id);
    }

    public function update(Order $order, string $status): ?Order
    {
        if (! $order->update(['status' => $status])) {
            return null;
        }

        return $order->fresh();
    }
}
