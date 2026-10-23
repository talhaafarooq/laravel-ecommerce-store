<?php

declare(strict_types = 1);

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\DTOs\UserFilters;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Concerns\BuildsLikeQueries;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class UserRepository implements UserRepositoryInterface
{
    use BuildsLikeQueries;

    public function paginateForAdmin(UserFilters $filters): LengthAwarePaginator
    {
        $query = User::query()
            ->withCount('orders')
            ->withSum($this->totalSpent(), 'grand_total');

        if ($filters->search !== null) {
            $search = $filters->search;

            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', $this->contains($search))
                    ->orWhere('email', 'like', $this->contains($search))
                    ->orWhere('phone', 'like', $this->contains($search));
            });
        }

        $query
            ->when($filters->role, static fn (Builder $query, UserRole $role) => $query->where('role', $role))
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('is_active', $filters->active));

        return $query->latest()->latest('id')->paginate($filters->perPage)->withQueryString();
    }

    public function loadAdminDetails(User $user): User
    {
        return $user
            ->loadCount('orders')
            ->loadSum($this->totalSpent(), 'grand_total')
            ->load(['addresses' => static fn ($query) => $query->orderByDesc('is_default')]);
    }

    public function countCustomers(): int
    {
        return User::query()->customers()->count();
    }

    public function countCustomersSince(CarbonInterface $since): int
    {
        return User::query()->customers()->where('created_at', '>=', $since)->count();
    }

    public function countActiveAdmins(): int
    {
        return User::query()->admins()->active()->count();
    }

    public function activeAdmins(): Collection
    {
        return User::query()->admins()->active()->get();
    }

    /**
     * Aggregate definition for the amount a customer has spent on counted sales.
     *
     * @return array<string, Closure(Builder<Order>): void>
     */
    private function totalSpent(): array
    {
        return [
            'orders as total_spent' => static function (Builder $query): void {
                /** @var Builder<Order> $query */
                $query->countedInSales();
            },
        ];
    }
}
