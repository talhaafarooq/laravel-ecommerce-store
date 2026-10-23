<?php

declare(strict_types = 1);

namespace App\Http\Requests\Account;

use App\DTOs\AddressData;
use App\Http\Requests\Concerns\AddressRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class AddressRequest extends FormRequest
{
    use AddressRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->addressRules(),
            'label'      => ['nullable', 'string', 'max:40'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function toData(): AddressData
    {
        return AddressData::fromArray($this->validated());
    }
}
