<?php

declare(strict_types = 1);

namespace App\DTOs;

use App\ValueObjects\Money;

/**
 * The editable attributes of a product, as submitted by an administrator.
 */
final readonly class ProductData
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public string $sku,
        public int $categoryId,
        public ?int $brandId,
        public ?string $shortDescription,
        public ?string $description,
        public Money $price,
        public ?Money $compareAtPrice,
        public int $stockQuantity,
        public int $lowStockThreshold,
        public bool $isActive,
        public bool $isFeatured,
        public ?string $metaTitle,
        public ?string $metaDescription,
    ) {}

    /**
     * Attributes for filling the product model. Stock is managed by the inventory service.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name'                => $this->name,
            'slug'                => $this->slug,
            'sku'                 => $this->sku,
            'category_id'         => $this->categoryId,
            'brand_id'            => $this->brandId,
            'short_description'   => $this->shortDescription,
            'description'         => $this->description,
            'price'               => $this->price,
            'compare_at_price'    => $this->compareAtPrice,
            'low_stock_threshold' => $this->lowStockThreshold,
            'is_active'           => $this->isActive,
            'is_featured'         => $this->isFeatured,
            'meta_title'          => $this->metaTitle,
            'meta_description'    => $this->metaDescription,
        ];
    }
}
