<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\DTOs\AdminProductFilters;
use App\Enums\StockStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProductIndexRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'    => ['nullable', 'string', 'max:100'],
            'category'  => ['nullable', 'integer'],
            'status'    => ['nullable', Rule::in(['active', 'inactive'])],
            'stock'     => ['nullable', Rule::enum(StockStatus::class)],
            'sort'      => ['nullable', Rule::in(['created_at', 'name', 'price', 'stock_quantity', 'sales_count'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'archived'  => ['nullable', 'boolean'],
        ];
    }

    public function filters(): AdminProductFilters
    {
        return new AdminProductFilters(
            search: $this->validated('search'),
            categoryId: $this->filled('category') ? (int) $this->validated('category') : null,
            active: match ($this->validated('status')) {
                'active'   => true,
                'inactive' => false,
                default    => null,
            },
            stock: StockStatus::tryFrom((string) $this->validated('stock')),
            sort: (string) ($this->validated('sort') ?? 'created_at'),
            direction: (string) ($this->validated('direction') ?? 'desc'),
            withTrashed: $this->boolean('archived'),
        );
    }
}
