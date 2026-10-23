<?php

declare(strict_types = 1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Media;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A snapshot of a purchased product, preserved even if the product changes.
 *
 * @property int                  $id
 * @property int                  $order_id
 * @property int|null             $product_id
 * @property string               $product_name
 * @property string               $product_sku
 * @property string|null          $product_image
 * @property string|null          $product_image_url
 * @property Money                $unit_price
 * @property int                  $quantity
 * @property Money                $line_total
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property Order                $order
 * @property Product|null         $product
 */
#[Fillable(['product_id', 'product_name', 'product_sku', 'product_image', 'unit_price', 'quantity', 'line_total'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function productImageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => Media::url($this->product_image));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => MoneyCast::class,
            'quantity'   => 'integer',
            'line_total' => MoneyCast::class,
        ];
    }
}
