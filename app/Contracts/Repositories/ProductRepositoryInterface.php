<?php

declare(strict_types = 1);

namespace App\Contracts\Repositories;

use App\DTOs\AdminProductFilters;
use App\DTOs\ProductFilters;
use App\Models\Brand;
use App\Models\Product;
use App\ValueObjects\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForStorefront(ProductFilters $filters): LengthAwarePaginator;

    /**
     * Find a storefront-visible product with everything its detail page needs.
     */
    public function findVisibleBySlug(string $slug): ?Product;

    /**
     * Find a storefront-visible product by id.
     */
    public function findVisible(int $id): ?Product;

    /**
     * @return Collection<int, Product>
     */
    public function related(Product $product, int $limit): Collection;

    /**
     * @return Collection<int, Product>
     */
    public function featured(int $limit): Collection;

    /**
     * @return Collection<int, Product>
     */
    public function newArrivals(int $limit): Collection;

    /**
     * @return Collection<int, Product>
     */
    public function bestSellers(int $limit): Collection;

    /**
     * @return Collection<int, Product>
     */
    public function onSale(int $limit): Collection;

    /**
     * Visible products for the given ids, preserving the order of the ids.
     *
     * @param list<int> $ids
     *
     * @return Collection<int, Product>
     */
    public function findVisibleByIds(array $ids): Collection;

    /**
     * @return Collection<int, Product>
     */
    public function searchSuggestions(string $term, int $limit): Collection;

    /**
     * @return array{min: Money, max: Money}
     */
    public function priceRange(): array;

    /**
     * Brands that have visible products in the given categories.
     *
     * @param list<int> $categoryIds
     *
     * @return Collection<int, Brand>
     */
    public function brandFacets(array $categoryIds = []): Collection;

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(AdminProductFilters $filters): LengthAwarePaginator;

    /**
     * @return Collection<int, Product>
     */
    public function lowStock(int $limit): Collection;

    public function countLowStock(): int;

    public function countActive(): int;

    /**
     * Lock the given products for the remainder of the current transaction.
     *
     * @param array<int, int> $ids
     *
     * @return Collection<int, Product> Keyed by product id.
     */
    public function lockForUpdate(array $ids, bool $withTrashed = false): Collection;
}
