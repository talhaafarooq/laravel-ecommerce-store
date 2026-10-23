<?php

declare(strict_types = 1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                       $id
 * @property int|null                  $user_id
 * @property string|null               $token
 * @property int|null                  $coupon_id
 * @property CarbonImmutable|null      $created_at
 * @property CarbonImmutable|null      $updated_at
 * @property User|null                 $user
 * @property Coupon|null               $coupon
 * @property Collection<int, CartItem> $items
 */
#[Fillable(['user_id', 'token', 'coupon_id'])]
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory, Prunable;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderBy('id');
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Abandoned guest carts are removed by the scheduled `model:prune` command.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->whereNull('user_id')
            ->where('updated_at', '<=', now()->subDays((int) config('shop.cart.guest_cart_lifetime_days', 30)));
    }
}
