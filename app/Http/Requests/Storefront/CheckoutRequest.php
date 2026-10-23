<?php

declare(strict_types = 1);

namespace App\Http\Requests\Storefront;

use App\DTOs\AddressData;
use App\DTOs\CheckoutData;
use App\Enums\PaymentMethod;
use App\Http\Requests\Concerns\AddressRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CheckoutRequest extends FormRequest
{
    use AddressRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->addressRules('shipping.'),
            'shipping'       => ['required', 'array'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'save_address'   => ['sometimes', 'boolean'],
        ];
    }

    public function toData(): CheckoutData
    {
        $notes = trim((string) $this->validated('notes', ''));

        return new CheckoutData(
            address: AddressData::fromArray((array) $this->validated('shipping')),
            paymentMethod: PaymentMethod::from((string) $this->validated('payment_method')),
            notes: $notes === '' ? null : $notes,
            saveAddress: $this->boolean('save_address'),
        );
    }
}
