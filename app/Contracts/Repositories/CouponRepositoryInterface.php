<?php

declare(strict_types = 1);

namespace App\Contracts\Repositories;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CouponRepositoryInterface
{
    public function findByCode(string $code): ?Coupon;

    /**
     * Re-read the coupon and lock it for the remainder of the current transaction.
     */
    public function lockForUpdate(Coupon $coupon): Coupon;

    /**
     * How many non-cancelled orders the user placed with the coupon.
     */
    public function usageCountForUser(Coupon $coupon, User $user): int;

    public function incrementUsage(Coupon $coupon): void;

    public function decrementUsage(Coupon $coupon): void;

    /**
     * @return LengthAwarePaginator<int, Coupon>
     */
    public function paginateForAdmin(?string $search = null, int $perPage = 15): LengthAwarePaginator;
}
