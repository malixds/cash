<?php

namespace App\Enums\PlayWalletEnums;

enum ResponseEnum: string
{
    case SUCCESS = 'success';
    case CREATED = 'created';
    case ERROR = 'error';
    case COMPLETED = 'completed';
    case ACTIVE = 'active';
    case QUEUED = 'queued';
}
