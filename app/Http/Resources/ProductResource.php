<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full storefront representation of a product.
 *
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = $this->whenLoaded('category', fn (): array => [
            'id'     => $this->category->id,
            'name'   => $this->category->name,
            'slug'   => $this->category->slug,
            'parent' => $this->category->relationLoaded('parent') && $this->category->parent !== null
                ? ['name' => $this->category->parent->name, 'slug' => $this->category->parent->slug]
                : null,
        ]);

        return [
            ...(new ProductCardResource($this->resource))->toArray($request),
            'description'      => $this->description,
            'description_html' => $this->descriptionHtml(),
            'stock_quantity'   => $this->stock_quantity,
            'max_quantity'     => max(0, min($this->stock_quantity, (int) config('shop.cart.max_quantity_per_item', 10))),
            'images'           => ProductImageResource::collection($this->whenLoaded('images')),
            'category'         => $category,
            'meta_title'       => $this->meta_title       ?? $this->name,
            'meta_description' => $this->meta_description ?? $this->short_description,
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
