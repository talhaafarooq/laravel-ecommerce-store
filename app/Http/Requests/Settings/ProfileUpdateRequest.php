<?php

declare(strict_types = 1);

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Http\Requests\Concerns\ImageRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ImageRules, ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            'phone'         => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{6,30}$/'],
            'avatar'        => ['nullable', $this->imageRule(2048)],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array{name: string, email: string, phone: string|null}
     */
    public function profileData(): array
    {
        return [
            'name'  => (string) $this->validated('name'),
            'email' => (string) $this->validated('email'),
            'phone' => $this->validated('phone'),
        ];
    }
}
