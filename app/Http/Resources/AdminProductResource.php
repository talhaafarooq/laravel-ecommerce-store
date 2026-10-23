<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The product representation used by the admin panel, exposing raw values.
 *
 * @mixin Product
 */
final class AdminProductResource extends JsonResource
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
            'category_id'         => $this->category_id,
            'brand_id'            => $this->brand_id,
            'category'            => $this->whenLoaded('category', fn (): array => ['id' => $this->category->id, 'name' => $this->category->name]),
            'brand'               => $this->whenLoaded('brand', fn (): ?array => $this->brand === null ? null : ['id' => $this->brand->id, 'name' => $this->brand->name]),
            'short_description'   => $this->short_description,
            'description'         => $this->description,
            'price'               => $this->price,
            'compare_at_price'    => $this->compare_at_price,
            'stock_quantity'      => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'stock_status'        => [...$stock->toOption(), 'tone' => $stock->tone()],
            'is_active'           => $this->is_active,
            'is_featured'         => $this->is_featured,
            'sales_count'         => $this->sales_count,
            'rating'              => round($this->rating_average, 1),
            'reviews_count'       => $this->reviews_count,
            'image'               => $this->relationLoaded('primaryImage') ? $this->primaryImage?->thumbnail_url : null,
            'images'              => ProductImageResource::collection($this->whenLoaded('images')),
            'meta_title'          => $this->meta_title,
            'meta_description'    => $this->meta_description,
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
            'deleted_at'          => $this->deleted_at?->toIso8601String(),
        ];
    }
}
