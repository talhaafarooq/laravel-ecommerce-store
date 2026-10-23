<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class FaqRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'topic'        => ['required', 'string', 'max:60'],
            'question'     => ['required', 'string', 'max:255'],
            'answer'       => ['required', 'string', 'max:5000'],
            'position'     => ['nullable', 'integer', 'min:0', 'max:10000'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function faqAttributes(): array
    {
        return [
            ...$this->validated(),
            'position'     => (int) $this->validated('position', 0),
            'is_published' => $this->boolean('is_published'),
        ];
    }
}
