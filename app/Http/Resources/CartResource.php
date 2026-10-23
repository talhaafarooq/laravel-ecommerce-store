<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\DTOs\CartLine;
use App\DTOs\CartSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CartSummary $resource
 */
final class CartResource extends JsonResource
{
    public function __construct(CartSummary $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $summary = $this->resource;

        return [
            'items' => array_map(static function (CartLine $line): array {
                $product = $line->product;
                $stock   = $product->stockStatus();

                return [
                    'id'      => $line->item->id,
                    'product' => [
                        'id'           => $product->id,
                        'name'         => $product->name,
                        'slug'         => $product->slug,
                        'sku'          => $product->sku,
                        'image'        => $product->relationLoaded('primaryImage') ? $product->primaryImage?->thumbnail_url : null,
                        'category'     => $product->relationLoaded('category') ? $product->category->name : null,
                        'stock_status' => [...$stock->toOption(), 'tone' => $stock->tone()],
                    ],
                    'quantity'     => $line->quantity,
                    'unit_price'   => $line->unitPrice,
                    'line_total'   => $line->lineTotal,
                    'max_quantity' => $line->maxQuantity,
                    'issue'        => $line->issue,
                ];
            }, $summary->lines),
            'totals' => $summary->totals->toArray(),
            'coupon' => $summary->coupon === null ? null : [
                'code'    => $summary->coupon->code,
                'summary' => $summary->coupon->summary(),
                'error'   => $summary->couponError,
            ],
            'item_count' => $summary->itemCount(),
            'has_issues' => $summary->hasIssues(),
            'is_empty'   => $summary->isEmpty(),
        ];
    }
}
