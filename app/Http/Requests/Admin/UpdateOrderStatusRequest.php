<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Whether the transition is allowed is enforced by the order service.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note'   => ['nullable', 'string', 'max:500'],
        ];
    }

    public function status(): OrderStatus
    {
        return OrderStatus::from((string) $this->validated('status'));
    }
}
