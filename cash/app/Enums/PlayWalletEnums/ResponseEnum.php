<?php

namespace App\Enums\PlayWalletEnums;

enum ResponseEnum: string
{
    case SUCCESS = 'success';
    case CREATED = 'created';
    case ERROR = 'error'; // НЕ ФАКТ, ЧТО ОН ТАКОЕ ОТПРАВЛЯЕТ
}
