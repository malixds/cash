<?php

namespace App\Enums\Order;

enum OrderStatusEnum: string
{
    case NEW = 'new';
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELED = 'canceled';
    case ERROR = 'error';
}
