<?php

namespace App\Enums\PlayWalletEnums;

enum ResponseMessages: string
{
    case DUPLICATE = 'Duplication order';

    public function isDuplicateMessage(string $message, string $externalId): bool
    {
        return str_starts_with($message, $this->value)
            && str_contains($message, $externalId);
    }
}
