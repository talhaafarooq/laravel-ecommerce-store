<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\DTOs\OrderFilters;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class OrderIndexRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'         => ['nullable', 'string', 'max:100'],
            'status'         => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'from'           => ['nullable', 'date_format:Y-m-d'],
            'to'             => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function filters(): OrderFilters
    {
        return new OrderFilters(
            search: $this->validated('search'),
            status: OrderStatus::tryFrom((string) $this->validated('status')),
            paymentStatus: PaymentStatus::tryFrom((string) $this->validated('payment_status')),
            paymentMethod: PaymentMethod::tryFrom((string) $this->validated('payment_method')),
            from: $this->filled('from') ? CarbonImmutable::parse((string) $this->validated('from'))->startOfDay() : null,
            to: $this->filled('to') ? CarbonImmutable::parse((string) $this->validated('to'))->endOfDay() : null,
        );
    }
}
