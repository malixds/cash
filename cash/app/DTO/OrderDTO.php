<?php

namespace App\DTO;

use Illuminate\Support\Str;

class OrderDTO
{
    public function __construct(
        public readonly string $steamLogin,
        public readonly int $amountRub,
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
            amountRub: max(0, (int) $validated['amount']),
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
        $totalRub = (int) ceil($this->amountRub * 1.05);

        return [
            'public_id' => $publicId,
            'steam_login' => $this->steamLogin,
            'region' => 'ru',
            'amount_rub' => $this->amountRub,
            'total_rub' => $totalRub,
            'promo_code' => $this->promoCode,
            'payment_method' => $this->paymentMethod,
            'status' => 'pending',
        ];
    }
}
