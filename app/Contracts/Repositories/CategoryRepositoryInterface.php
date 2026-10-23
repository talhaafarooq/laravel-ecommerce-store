<?php

declare(strict_types = 1);

namespace App\Contracts\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /**
     * Active root categories with their active children.
     *
     * @return Collection<int, Category>
     */
    public function activeTree(): Collection;

    public function findActiveBySlug(string $slug): ?Category;

    /**
     * The id of the category and of every category nested beneath it.
     *
     * @return list<int>
     */
    public function descendantIdsWithSelf(Category $category, bool $activeOnly = true): array;

    /**
     * Visible product counts keyed by category id.
     *
     * @return array<int, int>
     */
    public function visibleProductCounts(): array;

    /**
     * Active categories ranked by the number of visible products.
     *
     * @return Collection<int, Category>
     */
    public function popular(int $limit): Collection;

    /**
     * @return Collection<int, Category>
     */
    public function allForAdmin(?string $search = null): Collection;

    /**
     * Lightweight list for select inputs.
     *
     * @return Collection<int, Category>
     */
    public function options(): Collection;
}
