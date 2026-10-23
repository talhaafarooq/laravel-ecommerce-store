<?php

declare(strict_types = 1);

namespace App\Contracts\Repositories;

use App\DTOs\OrderFilters;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForCustomer(User $user, int $perPage = 10): LengthAwarePaginator;

    /**
     * @return Collection<int, Order>
     */
    public function recentForCustomer(User $user, int $limit): Collection;

    /**
     * @return array{orders: int, open: int, spent: int}
     */
    public function customerStats(User $user): array;

    /**
     * Eager load everything needed to display a single order.
     */
    public function loadDetails(Order $order): Order;

    public function findByNumber(string $number): ?Order;

    /**
     * Re-read the order and lock it for the remainder of the current transaction.
     */
    public function lockForUpdate(Order $order): Order;

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForAdmin(OrderFilters $filters): LengthAwarePaginator;

    /**
     * @return Collection<int, Order>
     */
    public function recent(int $limit): Collection;

    /**
     * Order counts keyed by status value.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array;

    /**
     * Online orders that were never paid and were placed before the cutoff.
     *
     * @return Collection<int, Order>
     */
    public function unpaidOnlineOrdersPlacedBefore(CarbonInterface $cutoff): Collection;
}
