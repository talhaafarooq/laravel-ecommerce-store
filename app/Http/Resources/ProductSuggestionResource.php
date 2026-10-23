<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The few fields the search typeahead needs.
 *
 * @mixin Product
 */
final class ProductSuggestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'slug'     => $this->slug,
            'price'    => $this->price,
            'image'    => $this->relationLoaded('primaryImage') ? $this->primaryImage?->thumbnail_url : null,
            'category' => $this->relationLoaded('category')
                ? ['name' => $this->category->name, 'slug' => $this->category->slug]
                : null,
        ];
    }
}
