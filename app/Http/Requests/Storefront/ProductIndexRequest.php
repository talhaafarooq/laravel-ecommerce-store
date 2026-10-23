<?php

declare(strict_types = 1);

namespace App\Http\Requests\Storefront;

use App\DTOs\ProductFilters;
use App\Enums\ProductSort;
use App\ValueObjects\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the catalogue filters shared by the storefront and the API.
 */
final class ProductIndexRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q'          => ['nullable', 'string', 'max:100'],
            'category'   => ['nullable', 'string', 'max:140'],
            'brands'     => ['nullable', 'array', 'max:20'],
            'brands.*'   => ['string', 'max:120'],
            'min_price'  => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'max_price'  => ['nullable', 'numeric', 'min:0', 'max:1000000', Rule::when($this->filled('min_price'), 'gte:min_price')],
            'min_rating' => ['nullable', 'integer', 'between:1,5'],
            'in_stock'   => ['nullable', 'boolean'],
            'on_sale'    => ['nullable', 'boolean'],
            'sort'       => ['nullable', Rule::enum(ProductSort::class)],
            'per_page'   => ['nullable', 'integer', Rule::in([12, 24, 48])],
        ];
    }

    public function filters(): ProductFilters
    {
        $search = trim((string) $this->validated('q', ''));

        return new ProductFilters(
            search: $search === '' ? null : $search,
            brands: array_values(array_map('strval', (array) $this->validated('brands', []))),
            minPrice: $this->filled('min_price') ? Money::fromDecimal((string) $this->validated('min_price')) : null,
            maxPrice: $this->filled('max_price') ? Money::fromDecimal((string) $this->validated('max_price')) : null,
            minRating: $this->filled('min_rating') ? (int) $this->validated('min_rating') : null,
            inStock: $this->boolean('in_stock'),
            onSale: $this->boolean('on_sale'),
            sort: ProductSort::tryFrom((string) $this->validated('sort')) ?? ($search === '' ? ProductSort::Newest : ProductSort::Popular),
            perPage: (int) ($this->validated('per_page') ?? config('shop.catalog.per_page', 12)),
        );
    }
}
