<?php

declare(strict_types = 1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\StockStatus;
use App\Models\Concerns\HasSlug;
use App\Support\Markdown;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int                           $id
 * @property int                           $category_id
 * @property int|null                      $brand_id
 * @property string                        $name
 * @property string                        $slug
 * @property string                        $sku
 * @property string|null                   $short_description
 * @property string|null                   $description
 * @property Money                         $price
 * @property Money|null                    $compare_at_price
 * @property int                           $stock_quantity
 * @property int                           $low_stock_threshold
 * @property bool                          $is_active
 * @property bool                          $is_featured
 * @property float                         $rating_average
 * @property int                           $reviews_count
 * @property int                           $sales_count
 * @property string|null                   $meta_title
 * @property string|null                   $meta_description
 * @property CarbonImmutable|null          $created_at
 * @property CarbonImmutable|null          $updated_at
 * @property CarbonImmutable|null          $deleted_at
 * @property Category                      $category
 * @property Brand|null                    $brand
 * @property Collection<int, ProductImage> $images
 * @property ProductImage|null             $primaryImage
 * @property Collection<int, Review>       $reviews
 */
#[Fillable([
    'category_id',
    'brand_id',
    'name',
    'slug',
    'sku',
    'short_description',
    'description',
    'price',
    'compare_at_price',
    'stock_quantity',
    'low_stock_threshold',
    'is_active',
    'is_featured',
    'meta_title',
    'meta_description',
])]
#[RouteKey('slug')]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasOne<ProductImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany(['position' => 'min', 'id' => 'min']);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price instanceof Money
            && $this->compare_at_price->greaterThan($this->price);
    }

    /**
     * Get the rounded discount percentage when the product is on sale.
     */
    public function discountPercentage(): ?int
    {
        if (!$this->isOnSale() || $this->compare_at_price === null) {
            return null;
        }

        return (int) round(
            ($this->compare_at_price->amount - $this->price->amount) / $this->compare_at_price->amount * 100,
        );
    }

    public function stockStatus(): StockStatus
    {
        return StockStatus::for($this->stock_quantity, $this->low_stock_threshold);
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Whether the product can currently be added to a cart.
     */
    public function isPurchasable(): bool
    {
        return $this->is_active && !$this->trashed() && $this->isInStock();
    }

    public function descriptionHtml(): string
    {
        return Markdown::toHtml($this->description);
    }

    /**
     * Products that are visible on the storefront.
     *
     * @param Builder<self> $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereHas('category', static fn (Builder $category) => $category->where('is_active', true));
    }

    /**
     * @param Builder<self> $query
     */
    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * @param Builder<self> $query
     */
    #[Scope]
    protected function inStock(Builder $query): void
    {
        $query->where('stock_quantity', '>', 0);
    }

    /**
     * @param Builder<self> $query
     */
    #[Scope]
    protected function onSale(Builder $query): void
    {
        $query->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price');
    }

    /**
     * @param Builder<self> $query
     */
    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price'               => MoneyCast::class,
            'compare_at_price'    => MoneyCast::class,
            'stock_quantity'      => 'integer',
            'low_stock_threshold' => 'integer',
            'is_active'           => 'boolean',
            'is_featured'         => 'boolean',
            'rating_average'      => 'float',
            'reviews_count'       => 'integer',
            'sales_count'         => 'integer',
        ];
    }
}
