<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\DTOs\ProductFilters;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\ValueObjects\Money;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Read-side use cases of the storefront catalogue.
 */
final readonly class CatalogService
{
    private const int SHORT_TTL = 600;

    public function __construct(
        private ProductRepositoryInterface $products,
        private CategoryRepositoryInterface $categories,
        private CatalogCache $cache,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function browse(ProductFilters $filters, ?Category $category = null): LengthAwarePaginator
    {
        if ($category !== null) {
            $filters = $filters->withCategoryIds($this->categoryScope($category));
        }

        return $this->products->paginateForStorefront($filters);
    }

    /**
     * @return array{brands: Collection<int, Brand>, price: array{min: Money, max: Money}}
     */
    public function filterOptions(?Category $category = null): array
    {
        // Money objects cannot be cached (the cache only unserializes plain data), so keep the amounts.
        $range = $this->cache->remember('price-range', self::SHORT_TTL, function (): array {
            $range = $this->products->priceRange();

            return ['min' => $range['min']->amount, 'max' => $range['max']->amount];
        });

        return [
            'brands' => $this->products->brandFacets($category === null ? [] : $this->categoryScope($category)),
            'price'  => ['min' => Money::of($range['min']), 'max' => Money::of($range['max'])],
        ];
    }

    /**
     * The active category tree used by the navigation menus.
     *
     * @return Collection<int, Category>
     */
    public function navigation(): Collection
    {
        return $this->rememberCategories(
            'navigation',
            (int) config('shop.catalog.navigation_cache_ttl', 3600),
            fn (): Collection => $this->categories->activeTree(),
        );
    }

    /**
     * Visible product counts per category, with children rolled up into their parents.
     *
     * @return array<int, int>
     */
    public function categoryCounts(): array
    {
        return $this->cache->remember('category-counts', self::SHORT_TTL, function (): array {
            $counts = $this->categories->visibleProductCounts();

            foreach ($this->navigation() as $root) {
                $total = $counts[$root->id] ?? 0;

                foreach ($root->children as $child) {
                    $total += $counts[$child->id] ?? 0;
                }

                $counts[$root->id] = $total;
            }

            return $counts;
        });
    }

    /**
     * @throws ModelNotFoundException<Category>
     */
    public function findCategory(string $slug): Category
    {
        return $this->categories->findActiveBySlug($slug)
            ?? throw (new ModelNotFoundException)->setModel(Category::class, [$slug]);
    }

    /**
     * @throws ModelNotFoundException<Product>
     */
    public function findProduct(string $slug): Product
    {
        return $this->products->findVisibleBySlug($slug)
            ?? throw (new ModelNotFoundException)->setModel(Product::class, [$slug]);
    }

    /**
     * @return Collection<int, Product>
     */
    public function related(Product $product): Collection
    {
        return $this->products->related($product, (int) config('shop.catalog.related_limit', 8));
    }

    /**
     * @return array{featured: Collection<int, Product>, new_arrivals: Collection<int, Product>, best_sellers: Collection<int, Product>, deals: Collection<int, Product>, categories: Collection<int, Category>}
     */
    public function homepage(): array
    {
        $limit = (int) config('shop.catalog.homepage_section_limit', 8);

        return [
            'featured'     => $this->products->featured($limit),
            'new_arrivals' => $this->products->newArrivals($limit),
            'best_sellers' => $this->products->bestSellers($limit),
            'deals'        => $this->products->onSale(4),
            'categories'   => $this->rememberCategories(
                'popular-categories',
                self::SHORT_TTL,
                fn (): Collection => $this->categories->popular(8),
            ),
        ];
    }

    /**
     * @return Collection<int, Product>
     */
    public function suggestions(string $term): Collection
    {
        return $this->products->searchSuggestions($term, (int) config('shop.catalog.search_suggestions_limit', 6));
    }

    /**
     * Forget all cached catalogue data after products or categories change.
     */
    public function flush(): void
    {
        $this->cache->flush();
    }

    /**
     * @return list<int>
     */
    private function categoryScope(Category $category): array
    {
        return $this->cache->remember(
            "category-scope:{$category->id}",
            self::SHORT_TTL,
            fn (): array => $this->categories->descendantIdsWithSelf($category),
        );
    }

    /**
     * Cache categories as plain attribute rows and hydrate models on the way out.
     *
     * The cache refuses to unserialize arbitrary classes (a deliberate safeguard
     * against gadget chains), so models and collections are never stored.
     *
     * @param Closure(): Collection<int, Category> $load
     *
     * @return Collection<int, Category>
     */
    private function rememberCategories(string $key, int $ttl, Closure $load): Collection
    {
        /** @var list<array{attributes: array<string, mixed>, children: list<array<string, mixed>>|null}> $rows */
        $rows = $this->cache->remember($key, $ttl, static fn (): array => $load()
            ->map(static fn (Category $category): array => [
                'attributes' => $category->getAttributes(),
                'children'   => $category->relationLoaded('children')
                    ? $category->children->map(static fn (Category $child): array => $child->getAttributes())->values()->all()
                    : null,
            ])
            ->values()
            ->all());

        $categories = Category::hydrate(array_column($rows, 'attributes'));

        foreach ($categories->values() as $index => $category) {
            if ($rows[$index]['children'] !== null) {
                $category->setRelation('children', Category::hydrate($rows[$index]['children']));
            }
        }

        return $categories;
    }
}
