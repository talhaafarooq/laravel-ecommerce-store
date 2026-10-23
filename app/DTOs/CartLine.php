<?php

declare(strict_types = 1);

namespace App\DTOs;

use App\Models\CartItem;
use App\Models\Product;
use App\ValueObjects\Money;

/**
 * A priced cart item, validated against the product's current state.
 */
final readonly class CartLine
{
    public function __construct(
        public CartItem $item,
        public Product $product,
        public int $quantity,
        public Money $unitPrice,
        public Money $lineTotal,
        public int $maxQuantity,
        public ?string $issue = null,
    ) {}

    public function isAvailable(): bool
    {
        return $this->issue === null;
    }
}
