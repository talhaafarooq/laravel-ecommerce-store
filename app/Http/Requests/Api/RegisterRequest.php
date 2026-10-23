<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Field presence only; the registration action applies the full rules.
 */
final class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string'],
            'email'                 => ['required', 'string'],
            'password'              => ['required', 'string'],
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
