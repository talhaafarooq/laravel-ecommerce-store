<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email'           => ['required', 'string', 'email', 'max:255'],
            'password'        => ['required', 'string', 'max:255'],
            'two_factor_code' => ['nullable', 'string', 'max:64'],
        ];
    }
}
