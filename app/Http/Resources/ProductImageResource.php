<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductImage
 */
final class ProductImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'url'       => $this->url,
            'thumbnail' => $this->thumbnail_url,
            'alt'       => $this->alt,
            'position'  => $this->position,
        ];
    }
}
