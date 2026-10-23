<?php

declare(strict_types = 1);

namespace App\Exceptions;

use App\ValueObjects\Money;

final class InvalidCouponException extends BusinessRuleException
{
    public static function notFound(): self
    {
        return new self('This coupon code is not valid.');
    }

    public static function inactive(): self
    {
        return new self('This coupon is no longer active.');
    }

    public static function notStarted(): self
    {
        return new self('This coupon is not active yet.');
    }

    public static function expired(): self
    {
        return new self('This coupon has expired.');
    }

    public static function exhausted(): self
    {
        return new self('This coupon has reached its usage limit.');
    }

    public static function alreadyUsed(): self
    {
        return new self('You have already used this coupon the maximum number of times.');
    }

    public static function minimumNotMet(Money $minimum): self
    {
        return new self("Spend at least {$minimum->format()} to use this coupon.");
    }

    public static function requiresAccount(): self
    {
        return new self('Please log in to use this coupon.');
    }

    public function errorKey(): string
    {
        return 'code';
    }
}
