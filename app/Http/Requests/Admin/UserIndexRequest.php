<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\DTOs\UserFilters;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UserIndexRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'role'   => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    public function filters(): UserFilters
    {
        return new UserFilters(
            search: $this->validated('search'),
            role: UserRole::tryFrom((string) $this->validated('role')),
            active: match ($this->validated('status')) {
                'active'   => true,
                'inactive' => false,
                default    => null,
            },
        );
    }
}
