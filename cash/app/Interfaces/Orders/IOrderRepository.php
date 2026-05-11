<?php

namespace App\Interfaces\Orders;

use App\Models\Order;

interface IOrderRepository
{
    public function find(int $id): ?Order;
    public function update(Order $order, string $status): ?Order;
}
