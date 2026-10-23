<?php

declare(strict_types = 1);

namespace App\Services;

use App\DTOs\OrderTotals;
use App\ValueObjects\Money;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Calculates shipping, tax and grand totals from a subtotal and discount.
 *
 * Shipping is a flat rate waived above a configurable threshold; tax is a
 * percentage of the discounted subtotal.
 */
final readonly class OrderTotalsCalculator
{
    public function __construct(private Config $config) {}

    public function calculate(Money $subtotal, ?Money $discount = null): OrderTotals
    {
        $discount   = ($discount ?? Money::zero())->nonNegative()->min($subtotal);
        $discounted = $subtotal->subtract($discount);
        $shipping   = $this->shippingFor($discounted);
        $tax        = $discounted->percentage($this->taxRate());

        return new OrderTotals(
            subtotal: $subtotal,
            discount: $discount,
            shipping: $shipping,
            tax: $tax,
            total: $discounted->add($shipping)->add($tax),
            freeShippingRemaining: $this->freeShippingRemaining($discounted),
        );
    }

    public function shippingFor(Money $amount): Money
    {
        if ($amount->isZero()) {
            return Money::zero();
        }

        $threshold = $this->freeShippingThreshold();

        if ($threshold !== null && $amount->greaterThanOrEqual($threshold)) {
            return Money::zero();
        }

        return Money::fromDecimal((float) $this->config->get('shop.shipping.flat_rate', 0));
    }

    public function freeShippingThreshold(): ?Money
    {
        $threshold = (float) $this->config->get('shop.shipping.free_threshold', 0);

        return $threshold > 0 ? Money::fromDecimal($threshold) : null;
    }

    private function freeShippingRemaining(Money $amount): ?Money
    {
        $threshold = $this->freeShippingThreshold();

        if ($threshold === null || $amount->isZero() || $amount->greaterThanOrEqual($threshold)) {
            return null;
        }

        return $threshold->subtract($amount);
    }

    private function taxRate(): float
    {
        return max(0.0, (float) $this->config->get('shop.tax_rate', 0));
    }
}
