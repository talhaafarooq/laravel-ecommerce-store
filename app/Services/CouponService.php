<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\CouponRepositoryInterface;
use App\Enums\CouponType;
use App\Exceptions\InvalidCouponException;
use App\Models\Coupon;
use App\Models\User;
use App\ValueObjects\Money;

final readonly class CouponService
{
    public function __construct(private CouponRepositoryInterface $coupons) {}

    /**
     * Find a coupon by code and ensure it can be applied.
     *
     * @throws InvalidCouponException
     */
    public function resolve(string $code, Money $subtotal, ?User $user): Coupon
    {
        $coupon = $this->coupons->findByCode($code) ?? throw InvalidCouponException::notFound();

        $this->assertApplicable($coupon, $subtotal, $user);

        return $coupon;
    }

    /**
     * @throws InvalidCouponException
     */
    public function assertApplicable(Coupon $coupon, Money $subtotal, ?User $user): void
    {
        match (true) {
            !$coupon->is_active    => throw InvalidCouponException::inactive(),
            !$coupon->hasStarted() => throw InvalidCouponException::notStarted(),
            $coupon->isExpired()   => throw InvalidCouponException::expired(),
            $coupon->isExhausted() => throw InvalidCouponException::exhausted(),
            default                => null,
        };

        if ($coupon->min_subtotal !== null && $subtotal->lessThan($coupon->min_subtotal)) {
            throw InvalidCouponException::minimumNotMet($coupon->min_subtotal);
        }

        if ($coupon->usage_limit_per_user === null) {
            return;
        }

        if ($user === null) {
            throw InvalidCouponException::requiresAccount();
        }

        if ($this->coupons->usageCountForUser($coupon, $user) >= $coupon->usage_limit_per_user) {
            throw InvalidCouponException::alreadyUsed();
        }
    }

    /**
     * The discount the coupon grants on the given subtotal.
     */
    public function discountFor(Coupon $coupon, Money $subtotal): Money
    {
        $discount = match ($coupon->type) {
            CouponType::Percentage => $subtotal->percentage((float) min($coupon->value, 100)),
            CouponType::Fixed      => Money::of($coupon->value),
        };

        if ($coupon->max_discount !== null) {
            $discount = $discount->min($coupon->max_discount);
        }

        return $discount->nonNegative()->min($subtotal);
    }

    /**
     * Lock, re-validate and consume one use of the coupon during checkout.
     *
     * Must be called inside a database transaction.
     *
     * @throws InvalidCouponException
     */
    public function redeem(Coupon $coupon, Money $subtotal, User $user): Coupon
    {
        $locked = $this->coupons->lockForUpdate($coupon);

        $this->assertApplicable($locked, $subtotal, $user);
        $this->coupons->incrementUsage($locked);

        return $locked;
    }

    /**
     * Give back a use of the coupon, e.g. when its order is cancelled.
     */
    public function release(Coupon $coupon): void
    {
        $this->coupons->decrementUsage($coupon);
    }
}
