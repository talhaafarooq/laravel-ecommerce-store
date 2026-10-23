<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The compact representation used by product grids and lists.
 *
 * @mixin Product
 */
final class ProductCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stock = $this->stockStatus();

        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'sku'                 => $this->sku,
            'short_description'   => $this->short_description,
            'price'               => $this->price,
            'compare_at_price'    => $this->isOnSale() ? $this->compare_at_price : null,
            'is_on_sale'          => $this->isOnSale(),
            'discount_percentage' => $this->discountPercentage(),
            'rating'              => round($this->rating_average, 1),
            'reviews_count'       => $this->reviews_count,
            'in_stock'            => $this->isInStock(),
            'stock_status'        => [...$stock->toOption(), 'tone' => $stock->tone()],
            'is_featured'         => $this->is_featured,
            'image'               => $this->relationLoaded('primaryImage') ? $this->primaryImage?->thumbnail_url : null,
            'brand'               => $this->relationLoaded('brand') && $this->brand !== null
                ? ['name' => $this->brand->name, 'slug' => $this->brand->slug]
                : null,
            'category' => $this->relationLoaded('category')
                ? ['name' => $this->category->name, 'slug' => $this->category->slug]
                : null,
        ];
    }
}
