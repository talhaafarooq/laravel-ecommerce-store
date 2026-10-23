<?php

declare(strict_types = 1);

namespace App\Contracts\Repositories;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ReviewRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Review>
     */
    public function paginateApprovedForProduct(Product $product, int $perPage = 6): LengthAwarePaginator;

    /**
     * Approved review counts keyed by star rating (5 to 1).
     *
     * @return array<int, int>
     */
    public function ratingBreakdown(Product $product): array;

    /**
     * @return array{average: float, count: int}
     */
    public function approvedAggregate(Product $product): array;

    public function findForUser(User $user, Product $product): ?Review;

    /**
     * Whether the user received a delivered order containing the product.
     */
    public function hasPurchased(User $user, Product $product): bool;

    /**
     * @return LengthAwarePaginator<int, Review>
     */
    public function paginateForAdmin(?string $search = null, ?bool $approved = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, Review>
     */
    public function latest(int $limit): Collection;

    public function countPending(): int;
}
