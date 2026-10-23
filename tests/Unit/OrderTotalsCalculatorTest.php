<?php

declare(strict_types = 1);

use App\Services\OrderTotalsCalculator;
use App\ValueObjects\Money;

beforeEach(function (): void {
    config([
        'shop.shipping.flat_rate'      => 5.00,
        'shop.shipping.free_threshold' => 50.00,
        'shop.tax_rate'                => 0,
    ]);
});

it('charges flat-rate shipping below the free shipping threshold', function (): void {
    $totals = app(OrderTotalsCalculator::class)->calculate(Money::fromDecimal(20));

    expect($totals->shipping->amount)->toBe(500)
        ->and($totals->total->amount)->toBe(2500)
        ->and($totals->freeShippingRemaining?->amount)->toBe(3000);
});

it('waives shipping at or above the threshold', function (): void {
    $totals = app(OrderTotalsCalculator::class)->calculate(Money::fromDecimal(50));

    expect($totals->shipping->isZero())->toBeTrue()
        ->and($totals->total->amount)->toBe(5000)
        ->and($totals->freeShippingRemaining)->toBeNull();
});

it('applies the discount before deciding on shipping', function (): void {
    $totals = app(OrderTotalsCalculator::class)->calculate(Money::fromDecimal(55), Money::fromDecimal(10));

    expect($totals->discount->amount)->toBe(1000)
        ->and($totals->shipping->amount)->toBe(500)
        ->and($totals->total->amount)->toBe(5000);
});

it('never lets a discount exceed the subtotal', function (): void {
    $totals = app(OrderTotalsCalculator::class)->calculate(Money::fromDecimal(10), Money::fromDecimal(99));

    expect($totals->discount->amount)->toBe(1000)
        ->and($totals->total->isZero())->toBeTrue();
});

it('charges nothing for an empty cart', function (): void {
    $totals = app(OrderTotalsCalculator::class)->calculate(Money::zero());

    expect($totals->shipping->isZero())->toBeTrue()
        ->and($totals->total->isZero())->toBeTrue();
});

it('adds tax on the discounted subtotal', function (): void {
    config(['shop.tax_rate' => 10]);

    $totals = app(OrderTotalsCalculator::class)->calculate(Money::fromDecimal(100), Money::fromDecimal(20));

    expect($totals->tax->amount)->toBe(800)
        ->and($totals->total->amount)->toBe(8800);
});
