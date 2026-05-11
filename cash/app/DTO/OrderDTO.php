<?php

namespace App\DTO;

use Illuminate\Support\Str;

class OrderDTO
{
    public function __construct(
        public readonly string $steamLogin,
        public readonly int $amount,
        public readonly ?string $promoCode,
        public readonly string $paymentMethod,
    ) {
    }

    /**
     * @param  array{login: string, amount: int|float|string, promo?: string|null, payment_method: string}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $promo = isset($validated['promo']) ? trim((string) $validated['promo']) : '';

        return new self(
            steamLogin: (string) $validated['login'],
            amount: max(0, (int) $validated['amount']),
            promoCode: $promo !== '' ? $promo : null,
            paymentMethod: (string) $validated['payment_method'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toOrderAttributes(): array
    {
        $publicId = (string) Str::uuid();
        $externalId = (string) Str::uuid();
        $total = (int) ceil($this->amount * 1.05);

        return [
            'public_id' => $publicId,
            'external_id' => $externalId,
            'steam_login' => $this->steamLogin,
            'region' => 'ru',
            'amount' => $this->amount,
            'total' => $total,
            'promo_code' => $this->promoCode,
            'payment_method' => $this->paymentMethod,
            'status' => 'pending',
        ];
    }
}
