<?php

declare(strict_types = 1);

namespace App\Repositories;

use App\Contracts\Repositories\CouponRepositoryInterface;
use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Concerns\BuildsLikeQueries;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class CouponRepository implements CouponRepositoryInterface
{
    use BuildsLikeQueries;

    public function findByCode(string $code): ?Coupon
    {
        return Coupon::query()->where('code', Str::upper(trim($code)))->first();
    }

    public function lockForUpdate(Coupon $coupon): Coupon
    {
        return Coupon::query()->whereKey($coupon->getKey())->lockForUpdate()->firstOrFail();
    }

    public function usageCountForUser(Coupon $coupon, User $user): int
    {
        return Order::query()
            ->where('coupon_id', $coupon->getKey())
            ->where('user_id', $user->getKey())
            ->where('status', '!=', OrderStatus::Cancelled)
            ->count();
    }

    public function incrementUsage(Coupon $coupon): void
    {
        Coupon::query()->whereKey($coupon->getKey())->increment('times_used');
    }

    public function decrementUsage(Coupon $coupon): void
    {
        Coupon::query()->whereKey($coupon->getKey())->where('times_used', '>', 0)->decrement('times_used');
    }

    public function paginateForAdmin(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return Coupon::query()
            ->when($search !== null, fn (Builder $query) => $query->where('code', 'like', $this->contains((string) $search)))
            ->latest()
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
