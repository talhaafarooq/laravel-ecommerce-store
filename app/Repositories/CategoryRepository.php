<?php

declare(strict_types = 1);

namespace App\Repositories;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Concerns\BuildsLikeQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class CategoryRepository implements CategoryRepositoryInterface
{
    use BuildsLikeQueries;

    public function activeTree(): Collection
    {
        return Category::query()
            ->active()
            ->roots()
            ->ordered()
            ->with(['children' => static fn ($query) => $query->where('is_active', true)])
            ->get();
    }

    public function findActiveBySlug(string $slug): ?Category
    {
        return Category::query()
            ->active()
            ->where('slug', $slug)
            ->with([
                'parent',
                'children' => static fn ($query) => $query->where('is_active', true),
            ])
            ->first();
    }

    public function descendantIdsWithSelf(Category $category, bool $activeOnly = true): array
    {
        $ids      = [$category->id];
        $frontier = [$category->id];

        while ($frontier !== []) {
            $children = Category::query()
                ->whereIn('parent_id', $frontier)
                ->when($activeOnly, static fn (Builder $query) => $query->where('is_active', true))
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            $frontier = array_values(array_diff($children, $ids));
            $ids      = [...$ids, ...$frontier];
        }

        return $ids;
    }

    public function visibleProductCounts(): array
    {
        return Product::query()
            ->visible()
            ->toBase()
            ->select('category_id')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id')
            ->mapWithKeys(static fn (mixed $count, mixed $categoryId): array => [(int) $categoryId => (int) $count])
            ->all();
    }

    public function popular(int $limit): Collection
    {
        return Category::query()
            ->active()
            ->withCount(['products' => static fn (Builder $query) => $query->where('is_active', true)])
            ->having('products_count', '>', 0)
            ->orderByDesc('products_count')
            ->limit($limit)
            ->get();
    }

    public function allForAdmin(?string $search = null): Collection
    {
        $categories = Category::query()
            ->with('parent:id,name')
            ->withCount(['products', 'children'])
            ->when($search !== null, fn (Builder $query) => $query->where('name', 'like', $this->contains((string) $search)))
            ->ordered()
            ->get();

        if ($search !== null) {
            return $categories;
        }

        // Order the flat list as a tree: each root followed by its children.
        $byParent = $categories->groupBy(static fn (Category $category): int => $category->parent_id ?? 0);
        $ordered  = new Collection;

        $append = static function (int $parentId) use (&$append, $byParent, $ordered): void {
            foreach ($byParent->get($parentId, []) as $category) {
                $ordered->push($category);
                $append($category->id);
            }
        };

        $append(0);

        return $ordered;
    }

    public function options(): Collection
    {
        return Category::query()
            ->with('parent:id,name')
            ->ordered()
            ->get(['id', 'parent_id', 'name', 'is_active']);
    }
}
