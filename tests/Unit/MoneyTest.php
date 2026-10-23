<?php

declare(strict_types = 1);

use App\ValueObjects\Money;

test('amounts are held in minor units and parsed from decimals without float drift', function (): void {
    expect(Money::fromDecimal('19.99')->amount)->toBe(1999)
        ->and(Money::fromDecimal(0.1 + 0.2)->amount)->toBe(30)
        ->and(Money::fromDecimal('1234.5')->amount)->toBe(123450)
        ->and(Money::zero()->isZero())->toBeTrue();
});

test('invalid decimals are rejected', function (): void {
    Money::fromDecimal('abc');
})->throws(InvalidArgumentException::class);

test('it adds, subtracts and scales', function (): void {
    $price = Money::of(1999);

    expect($price->add(Money::of(1))->amount)->toBe(2000)
        ->and($price->subtract(Money::of(999))->amount)->toBe(1000)
        ->and($price->multiply(3)->amount)->toBe(5997)
        ->and($price->percentage(10)->amount)->toBe(200)
        ->and($price->percentage(12.5)->amount)->toBe(250);
});

test('arithmetic between different currencies is refused', function (): void {
    Money::of(100, 'USD')->add(Money::of(100, 'EUR'));
})->throws(InvalidArgumentException::class);

test('min, max and clamping behave', function (): void {
    $small = Money::of(100);
    $large = Money::of(500);

    expect($small->min($large))->toBe($small)
        ->and($small->max($large))->toBe($large)
        ->and(Money::of(-50)->nonNegative()->amount)->toBe(0)
        ->and($large->greaterThan($small))->toBeTrue()
        ->and($small->greaterThanOrEqual(Money::of(100)))->toBeTrue()
        ->and($small->lessThan($large))->toBeTrue()
        ->and($small->equals(Money::of(100)))->toBeTrue();
});

test('it formats and serialises for the frontend', function (): void {
    $json = Money::of(123456)->jsonSerialize();

    expect($json)->toBe([
        'amount'    => 123456,
        'decimal'   => '1234.56',
        'formatted' => '$1,234.56',
        'currency'  => 'USD',
    ])->and((string) Money::of(500))->toBe('$5.00');
});
