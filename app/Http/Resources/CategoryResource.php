<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'image'            => $this->image_url,
            'parent_id'        => $this->parent_id,
            'is_active'        => $this->is_active,
            'position'         => $this->position,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'products_count'   => $this->whenCounted('products'),
            'children_count'   => $this->whenCounted('children'),
            'parent'           => $this->whenLoaded('parent', fn (): ?array => $this->parent === null ? null : [
                'id'   => $this->parent->id,
                'name' => $this->parent->name,
                'slug' => $this->parent->slug,
            ]),
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
