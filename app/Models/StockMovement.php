<?php

declare(strict_types = 1);

namespace App\Models;

use App\Enums\StockMovementType;
use Carbon\CarbonImmutable;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable entry in a product's inventory ledger.
 *
 * @property int                  $id
 * @property int                  $product_id
 * @property int|null             $order_id
 * @property int|null             $user_id
 * @property StockMovementType    $type
 * @property int                  $quantity      Signed change applied to the stock level.
 * @property int                  $balance_after
 * @property string|null          $note
 * @property CarbonImmutable|null $created_at
 * @property Product              $product
 * @property Order|null           $order
 * @property User|null            $user
 */
#[Fillable(['order_id', 'user_id', 'type', 'quantity', 'balance_after', 'note'])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'          => StockMovementType::class,
            'quantity'      => 'integer',
            'balance_after' => 'integer',
        ];
    }
}
