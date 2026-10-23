<?php

declare(strict_types = 1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\CouponType;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int                  $id
 * @property string               $code
 * @property string|null          $description
 * @property CouponType           $type
 * @property int                  $value                Percentage (1-100) or fixed amount in minor units, depending on the type.
 * @property Money|null           $max_discount
 * @property Money|null           $min_subtotal
 * @property int|null             $usage_limit
 * @property int|null             $usage_limit_per_user
 * @property int                  $times_used
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $expires_at
 * @property bool                 $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'code',
    'description',
    'type',
    'value',
    'max_discount',
    'min_subtotal',
    'usage_limit',
    'usage_limit_per_user',
    'starts_at',
    'expires_at',
    'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function hasStarted(): bool
    {
        return $this->starts_at === null || $this->starts_at->isPast();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->usage_limit !== null && $this->times_used >= $this->usage_limit;
    }

    /**
     * A short human readable summary, e.g. "15% off" or "$10.00 off".
     */
    public function summary(): string
    {
        return match ($this->type) {
            CouponType::Percentage => "{$this->value}% off",
            CouponType::Fixed      => Money::of($this->value)->format() . ' off',
        };
    }

    /**
     * Normalise coupon codes to upper case without surrounding whitespace.
     *
     * @return Attribute<string, string>
     */
    protected function code(): Attribute
    {
        return Attribute::make(
            get: static fn (string $value): string => $value,
            set: static fn (string $value): string => Str::upper(trim($value)),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'                 => CouponType::class,
            'value'                => 'integer',
            'max_discount'         => MoneyCast::class,
            'min_subtotal'         => MoneyCast::class,
            'usage_limit'          => 'integer',
            'usage_limit_per_user' => 'integer',
            'times_used'           => 'integer',
            'starts_at'            => 'datetime',
            'expires_at'           => 'datetime',
            'is_active'            => 'boolean',
        ];
    }
}
