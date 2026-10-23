<?php

declare(strict_types = 1);

namespace App\ValueObjects;

use Illuminate\Support\Number;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable monetary amount stored in the currency's minor unit (e.g. cents).
 *
 * The store operates in a single two-decimal currency configured in
 * config/shop.php, so arithmetic never needs currency conversion.
 */
final readonly class Money implements JsonSerializable, Stringable
{
    private function __construct(
        public int $amount,
        public string $currency,
    ) {}

    public function __toString(): string
    {
        return $this->format();
    }

    public static function of(int $amount, ?string $currency = null): self
    {
        return new self($amount, $currency ?? self::defaultCurrency());
    }

    public static function zero(?string $currency = null): self
    {
        return self::of(0, $currency);
    }

    /**
     * Create an amount from a major-unit value such as "19.99".
     */
    public static function fromDecimal(float|int|string $value, ?string $currency = null): self
    {
        if (is_string($value) && !is_numeric($value)) {
            throw new InvalidArgumentException("[{$value}] is not a valid monetary value.");
        }

        return self::of((int) round(((float) $value) * 100), $currency);
    }

    public static function defaultCurrency(): string
    {
        return strtoupper((string) config('shop.currency.code', 'USD'));
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int|float $multiplier): self
    {
        return new self((int) round($this->amount * $multiplier), $this->currency);
    }

    /**
     * Get the given percentage of the amount, rounded to the nearest minor unit.
     */
    public function percentage(float $percent): self
    {
        return new self((int) round($this->amount * $percent / 100), $this->currency);
    }

    public function min(self $other): self
    {
        $this->assertSameCurrency($other);

        return $this->amount <= $other->amount ? $this : $other;
    }

    public function max(self $other): self
    {
        $this->assertSameCurrency($other);

        return $this->amount >= $other->amount ? $this : $other;
    }

    /**
     * Clamp negative amounts to zero.
     */
    public function nonNegative(): self
    {
        return $this->amount < 0 ? new self(0, $this->currency) : $this;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function greaterThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount >= $other->amount;
    }

    public function lessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount < $other->amount;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->amount === $other->amount;
    }

    /**
     * Get the major-unit representation, e.g. "19.99".
     */
    public function toDecimal(): string
    {
        return number_format($this->amount / 100, 2, '.', '');
    }

    /**
     * Get the localized representation, e.g. "$19.99".
     */
    public function format(): string
    {
        $formatted = Number::currency(
            $this->amount / 100,
            in: $this->currency,
            locale: (string) config('shop.currency.locale', 'en_US'),
        );

        return is_string($formatted) ? $formatted : $this->currency . ' ' . $this->toDecimal();
    }

    /**
     * @return array{amount: int, decimal: string, formatted: string, currency: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'amount'    => $this->amount,
            'decimal'   => $this->toDecimal(),
            'formatted' => $this->format(),
            'currency'  => $this->currency,
        ];
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot combine amounts in [{$this->currency}] and [{$other->currency}].",
            );
        }
    }
}
