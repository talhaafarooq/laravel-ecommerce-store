<?php

declare(strict_types = 1);

namespace App\Http\Requests\Storefront;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ContactRequest extends FormRequest
{
    /**
     * The "website" field is a honeypot that real visitors never see.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'string', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array{name: string, email: string, phone: string|null, subject: string, message: string}
     */
    public function messageData(): array
    {
        return [
            'name'    => (string) $this->validated('name'),
            'email'   => (string) $this->validated('email'),
            'phone'   => $this->validated('phone'),
            'subject' => (string) $this->validated('subject'),
            'message' => (string) $this->validated('message'),
        ];
    }
}
