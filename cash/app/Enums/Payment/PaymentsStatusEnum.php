<?php

namespace App\Enums\Payment;

enum PaymentsStatusEnum: string
{
    case NEW = 'new';
    case PENDING = 'pending';
    case PAID = 'paid';
    case SUCCEEDED = 'succeeded';
    case CANCELED = 'canceled';
    case ERROR = 'error';
}
