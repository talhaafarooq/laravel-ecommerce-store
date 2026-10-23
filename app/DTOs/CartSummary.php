<?php

declare(strict_types = 1);

namespace App\DTOs;

use App\Models\Cart;
use App\Models\Coupon;

final readonly class CartSummary
{
    /**
     * @param list<CartLine> $lines
     */
    public function __construct(
        public ?Cart $cart,
        public array $lines,
        public OrderTotals $totals,
        public ?Coupon $coupon = null,
        public ?string $couponError = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /**
     * The total number of units in the cart.
     */
    public function itemCount(): int
    {
        return array_sum(array_map(static fn (CartLine $line): int => $line->quantity, $this->lines));
    }

    public function hasIssues(): bool
    {
        foreach ($this->lines as $line) {
            if (!$line->isAvailable()) {
                return true;
            }
        }

        return false;
    }
}
