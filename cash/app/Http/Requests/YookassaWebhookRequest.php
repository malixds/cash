<?php

namespace App\Http\Requests;

use App\DTO\Payments\PaymentProviderWebhookDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class YookassaWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string'],
            'object.id' => ['required', 'string'],
            'object.status' => ['required', 'string'],
            'object.amount.value' => ['required', 'numeric'],
            'object.amount.currency' => ['required', 'string', 'in:RUB'],
            'object.created_at' => ['required', 'date'],
            'object.captured_at' => ['nullable', 'date'],
            'object.paid' => ['required', 'boolean'],
        ];
    }

    public function toDto(): PaymentProviderWebhookDTO
    {
        return new PaymentProviderWebhookDTO(
            type: $this->input('type'),
            status: $this->input('object.status'),
            id: $this->input('object.id'),
            amount: (float) $this->input('object.amount.value'),
            created_at: $this->input('object.created_at'),
            captured_at: $this->input('object.captured_at'),
            isPaid: $this->boolean('object.paid'),
        );
    }
}
