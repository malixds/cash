<?php

namespace App\Http\Requests;

use App\DTO\Payments\PaymentProviderWebhookDTO;
use App\Enums\Payment\PaymentsStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class EnotWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'string'],
            'status' => ['required', 'string', 'in:success,fail,expired,refund'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'in:RUB'],
            'order_id' => ['required', 'string'],
            'type' => ['nullable', 'integer'],
            'pay_time' => ['nullable', 'string'],
            'reject_time' => ['nullable', 'string'],
        ];
    }

    public function toDto(): PaymentProviderWebhookDTO
    {
        $enotStatus = (string) $this->input('status');

        return new PaymentProviderWebhookDTO(
            type: (string) $this->input('type', ''),
            status: self::mapStatus($enotStatus),
            id: (string) $this->input('invoice_id'),
            amount: (float) $this->input('amount'),
            created_at: (string) ($this->input('pay_time') ?? $this->input('reject_time') ?? ''),
            captured_at: $this->input('pay_time'),
            isPaid: $enotStatus === 'success',
        );
    }

    /**
     * Маппинг статусов Enot на внутренний PaymentsStatusEnum.
     */
    private static function mapStatus(string $enotStatus): string
    {
        return match ($enotStatus) {
            'success' => PaymentsStatusEnum::SUCCEEDED->value,
            'refund' => PaymentsStatusEnum::REFUNDED->value,
            default => PaymentsStatusEnum::CANCELED->value, // fail | expired
        };
    }
}
