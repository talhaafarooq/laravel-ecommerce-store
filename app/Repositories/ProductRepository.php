<?php

declare(strict_types = 1);

namespace App\Repositories;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\DTOs\AdminProductFilters;
use App\DTOs\ProductFilters;
use App\Enums\ProductSort;
use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Concerns\BuildsLikeQueries;
use App\ValueObjects\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ProductRepository implements ProductRepositoryInterface
{
    use BuildsLikeQueries;

    /**
     * The columns required to render a product card.
     *
     * @var list<string>
     */
    private const array CARD_COLUMNS = [
        'products.id',
        'products.category_id',
        'products.brand_id',
        'products.name',
        'products.slug',
        'products.sku',
        'products.short_description',
        'products.price',
        'products.compare_at_price',
        'products.stock_quantity',
        'products.low_stock_threshold',
        'products.is_active',
        'products.is_featured',
        'products.rating_average',
        'products.reviews_count',
        'products.sales_count',
        'products.created_at',
        'products.deleted_at',
    ];

    /**
     * Admin sortable columns mapped to database columns.
     *
     * @var array<string, string>
     */
    private const array ADMIN_SORTS = [
        'name'       => 'name',
        'price'      => 'price',
        'stock'      => 'stock_quantity',
        'sales'      => 'sales_count',
        'rating'     => 'rating_average',
        'created_at' => 'created_at',
    ];

    public function paginateForStorefront(ProductFilters $filters): LengthAwarePaginator
    {
        $query = $this->cardQuery();

        $this->applyStorefrontFilters($query, $filters);
        $this->applySort($query, $filters->sort, $filters->search);

        return $query->paginate($filters->perPage)->withQueryString();
    }

    public function findVisibleBySlug(string $slug): ?Product
    {
        return Product::query()
            ->visible()
            ->where('slug', $slug)
            ->with(['images', 'brand:id,name,slug', 'category.parent'])
            ->first();
    }

    public function findVisible(int $id): ?Product
    {
        return Product::query()
            ->visible()
            ->with(['primaryImage', 'category:id,name,slug,is_active'])
            ->find($id);
    }

    public function related(Product $product, int $limit): Collection
    {
        $product->loadMissing('category');

        $categoryIds = [$product->category_id];

        if ($product->category->parent_id !== null) {
            $siblingIds = Category::query()
                ->where('parent_id', $product->category->parent_id)
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            $categoryIds = array_values(array_unique([...$categoryIds, ...$siblingIds]));
        }

        return $this->cardQuery()
            ->whereIn('products.category_id', $categoryIds)
            ->whereKeyNot($product->id)
            ->orderByRaw('CASE WHEN products.category_id = ? THEN 0 ELSE 1 END', [$product->category_id])
            ->orderByDesc('products.sales_count')
            ->orderByDesc('products.rating_average')
            ->limit($limit)
            ->get();
    }

    public function featured(int $limit): Collection
    {
        return $this->cardQuery()
            ->featured()
            ->orderByDesc('products.sales_count')
            ->limit($limit)
            ->get();
    }

    public function newArrivals(int $limit): Collection
    {
        return $this->cardQuery()
            ->latest('products.created_at')
            ->latest('products.id')
            ->limit($limit)
            ->get();
    }

    public function bestSellers(int $limit): Collection
    {
        return $this->cardQuery()
            ->where('products.sales_count', '>', 0)
            ->orderByDesc('products.sales_count')
            ->limit($limit)
            ->get();
    }

    public function onSale(int $limit): Collection
    {
        return $this->cardQuery()
            ->onSale()
            ->inStock()
            ->orderByRaw('(products.compare_at_price - products.price) / products.compare_at_price DESC')
            ->limit($limit)
            ->get();
    }

    public function findVisibleByIds(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        $positions = array_flip($ids);

        return $this->cardQuery()
            ->whereKey($ids)
            ->get()
            ->sortBy(static fn (Product $product): int => $positions[$product->id] ?? PHP_INT_MAX)
            ->values();
    }

    public function searchSuggestions(string $term, int $limit): Collection
    {
        $query = Product::query()
            ->select(['products.id', 'products.category_id', 'products.name', 'products.slug', 'products.price', 'products.compare_at_price'])
            ->visible()
            ->with(['primaryImage', 'category:id,name,slug']);

        $this->applySearch($query, $term);

        return $query
            ->orderByRaw('CASE WHEN products.name LIKE ? THEN 0 ELSE 1 END', [$this->startsWith($term)])
            ->orderByDesc('products.sales_count')
            ->limit($limit)
            ->get();
    }

    public function priceRange(): array
    {
        $query = Product::query()->visible();

        return [
            'min' => Money::of((int) (clone $query)->min('price')),
            'max' => Money::of((int) (clone $query)->max('price')),
        ];
    }

    public function brandFacets(array $categoryIds = []): Collection
    {
        $constraint = static function (Builder $query) use ($categoryIds): void {
            /** @var Builder<Product> $query */
            $query->visible()->when(
                $categoryIds !== [],
                static fn (Builder $query) => $query->whereIn('category_id', $categoryIds),
            );
        };

        return Brand::query()
            ->active()
            ->whereHas('products', $constraint)
            ->withCount(['products' => $constraint])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    public function paginateForAdmin(AdminProductFilters $filters): LengthAwarePaginator
    {
        $query = Product::query()->with(['primaryImage', 'category:id,name', 'brand:id,name']);

        if ($filters->withTrashed) {
            $query->onlyTrashed();
        }

        if ($filters->search !== null) {
            $search = $filters->search;

            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', $this->contains($search))
                    ->orWhere('sku', 'like', $this->startsWith($search));
            });
        }

        if ($filters->categoryId !== null) {
            $categoryId = $filters->categoryId;

            $query->where(static function (Builder $query) use ($categoryId): void {
                $query->where('category_id', $categoryId)
                    ->orWhereIn('category_id', Category::query()->select('id')->where('parent_id', $categoryId));
            });
        }

        if ($filters->active !== null) {
            $query->where('is_active', $filters->active);
        }

        match ($filters->stock) {
            StockStatus::OutOfStock => $query->where('stock_quantity', 0),
            StockStatus::LowStock   => $query->where('stock_quantity', '>', 0)->lowStock(),
            StockStatus::InStock    => $query->whereColumn('stock_quantity', '>', 'low_stock_threshold'),
            null                    => null,
        };

        $column    = self::ADMIN_SORTS[$filters->sort] ?? 'created_at';
        $direction = $filters->direction === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy($column, $direction)
            ->orderBy('id', $direction)
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function lowStock(int $limit): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->lowStock()
            ->with('primaryImage')
            ->orderBy('stock_quantity')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'sku', 'stock_quantity', 'low_stock_threshold']);
    }

    public function countLowStock(): int
    {
        return Product::query()->where('is_active', true)->lowStock()->count();
    }

    public function countActive(): int
    {
        return Product::query()->where('is_active', true)->count();
    }

    public function lockForUpdate(array $ids, bool $withTrashed = false): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return Product::query()
            ->when($withTrashed, static fn (Builder $query) => $query->withTrashed())
            ->whereKey($ids)
            ->with(['category:id,is_active', 'primaryImage'])
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @return Builder<Product>
     */
    private function cardQuery(): Builder
    {
        return Product::query()
            ->select(self::CARD_COLUMNS)
            ->visible()
            ->with(['primaryImage', 'brand:id,name,slug', 'category:id,name,slug']);
    }

    /**
     * @param Builder<Product> $query
     */
    private function applyStorefrontFilters(Builder $query, ProductFilters $filters): void
    {
        if ($filters->search !== null) {
            $this->applySearch($query, $filters->search);
        }

        if ($filters->categoryIds !== []) {
            $query->whereIn('products.category_id', $filters->categoryIds);
        }

        if ($filters->brands !== []) {
            $query->whereIn('products.brand_id', Brand::query()->select('id')->whereIn('slug', $filters->brands));
        }

        if ($filters->minPrice !== null) {
            $query->where('products.price', '>=', $filters->minPrice->amount);
        }

        if ($filters->maxPrice !== null) {
            $query->where('products.price', '<=', $filters->maxPrice->amount);
        }

        if ($filters->minRating !== null) {
            $query->where('products.rating_average', '>=', $filters->minRating);
        }

        if ($filters->inStock) {
            $query->inStock();
        }

        if ($filters->onSale) {
            $query->onSale();
        }
    }

    /**
     * Every search term must match the name, description, SKU, brand or category.
     *
     * @param Builder<Product> $query
     */
    private function applySearch(Builder $query, string $phrase): void
    {
        foreach ($this->searchTerms($phrase) as $term) {
            $like = $this->contains($term);

            $query->where(static function (Builder $query) use ($like, $term): void {
                $query->where('products.name', 'like', $like)
                    ->orWhere('products.short_description', 'like', $like)
                    ->orWhere('products.sku', '=', $term)
                    ->orWhereHas('brand', static fn (Builder $brand) => $brand->where('name', 'like', $like))
                    ->orWhereHas('category', static fn (Builder $category) => $category->where('name', 'like', $like));
            });
        }
    }

    /**
     * @param Builder<Product> $query
     */
    private function applySort(Builder $query, ProductSort $sort, ?string $search): void
    {
        if ($search !== null && $sort === ProductSort::Newest) {
            $query->orderByRaw('CASE WHEN products.name LIKE ? THEN 0 ELSE 1 END', [$this->startsWith($search)]);
        }

        match ($sort) {
            ProductSort::Newest         => $query->latest('products.created_at'),
            ProductSort::Popular        => $query->orderByDesc('products.sales_count'),
            ProductSort::TopRated       => $query->orderByDesc('products.rating_average')->orderByDesc('products.reviews_count'),
            ProductSort::PriceLowToHigh => $query->orderBy('products.price'),
            ProductSort::PriceHighToLow => $query->orderByDesc('products.price'),
            ProductSort::NameAsc        => $query->orderBy('products.name'),
        };

        $query->orderByDesc('products.id');
    }
}
