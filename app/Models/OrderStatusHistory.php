<?php

declare(strict_types = 1);

namespace App\Models;

use App\Enums\OrderStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                  $id
 * @property int                  $order_id
 * @property int|null             $user_id
 * @property OrderStatus|null     $from_status
 * @property OrderStatus          $to_status
 * @property string|null          $note
 * @property CarbonImmutable|null $created_at
 * @property Order                $order
 * @property User|null            $user
 */
#[Fillable(['user_id', 'from_status', 'to_status', 'note'])]
class OrderStatusHistory extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The user responsible for the change, when it was not automatic.
     *
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
            'from_status' => OrderStatus::class,
            'to_status'   => OrderStatus::class,
        ];
    }
}
