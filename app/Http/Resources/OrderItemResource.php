<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
final class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->relationLoaded('product') ? $this->product : null;

        return [
            'id'           => $this->id,
            'product_id'   => $this->product_id,
            'product_slug' => $product !== null && !$product->trashed() ? $product->slug : null,
            'name'         => $this->product_name,
            'sku'          => $this->product_sku,
            'image'        => $this->product_image_url,
            'unit_price'   => $this->unit_price,
            'quantity'     => $this->quantity,
            'line_total'   => $this->line_total,
        ];
    }
}
