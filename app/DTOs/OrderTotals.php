<?php

declare(strict_types = 1);

namespace App\DTOs;

use App\ValueObjects\Money;

final readonly class OrderTotals
{
    public function __construct(
        public Money $subtotal,
        public Money $discount,
        public Money $shipping,
        public Money $tax,
        public Money $total,
        public ?Money $freeShippingRemaining = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subtotal'                => $this->subtotal,
            'discount'                => $this->discount,
            'shipping'                => $this->shipping,
            'tax'                     => $this->tax,
            'total'                   => $this->total,
            'free_shipping_remaining' => $this->freeShippingRemaining,
        ];
    }
}
