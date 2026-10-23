<?php

declare(strict_types = 1);

namespace App\DTOs;

use App\Enums\ProductSort;
use App\ValueObjects\Money;

/**
 * Storefront catalogue filters parsed from the query string.
 */
final readonly class ProductFilters
{
    /**
     * @param list<int>    $categoryIds Category ids (including descendants) the products must belong to.
     * @param list<string> $brands      Brand slugs.
     */
    public function __construct(
        public ?string $search = null,
        public array $categoryIds = [],
        public array $brands = [],
        public ?Money $minPrice = null,
        public ?Money $maxPrice = null,
        public ?int $minRating = null,
        public bool $inStock = false,
        public bool $onSale = false,
        public ProductSort $sort = ProductSort::Newest,
        public int $perPage = 12,
    ) {}

    /**
     * @param list<int> $categoryIds
     */
    public function withCategoryIds(array $categoryIds): self
    {
        return new self(
            search: $this->search,
            categoryIds: $categoryIds,
            brands: $this->brands,
            minPrice: $this->minPrice,
            maxPrice: $this->maxPrice,
            minRating: $this->minRating,
            inStock: $this->inStock,
            onSale: $this->onSale,
            sort: $this->sort,
            perPage: $this->perPage,
        );
    }

    /**
     * The active filters in a frontend friendly shape.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'search'     => $this->search,
            'brands'     => $this->brands,
            'min_price'  => $this->minPrice?->toDecimal(),
            'max_price'  => $this->maxPrice?->toDecimal(),
            'min_rating' => $this->minRating,
            'in_stock'   => $this->inStock,
            'on_sale'    => $this->onSale,
            'sort'       => $this->sort->value,
        ];
    }
}
