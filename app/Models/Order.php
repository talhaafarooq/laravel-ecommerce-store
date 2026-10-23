<?php

declare(strict_types = 1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\ValueObjects\DateRange;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int                                 $id
 * @property string                              $number
 * @property int|null                            $user_id
 * @property int|null                            $coupon_id
 * @property OrderStatus                         $status
 * @property PaymentStatus                       $payment_status
 * @property PaymentMethod                       $payment_method
 * @property string                              $currency
 * @property Money                               $subtotal
 * @property Money                               $discount_total
 * @property Money                               $shipping_total
 * @property Money                               $tax_total
 * @property Money                               $grand_total
 * @property string|null                         $coupon_code
 * @property string                              $customer_email
 * @property string                              $shipping_name
 * @property string                              $shipping_phone
 * @property string                              $shipping_line1
 * @property string|null                         $shipping_line2
 * @property string                              $shipping_city
 * @property string|null                         $shipping_state
 * @property string                              $shipping_postal_code
 * @property string                              $shipping_country
 * @property string|null                         $notes
 * @property string|null                         $cancellation_reason
 * @property CarbonImmutable                     $placed_at
 * @property CarbonImmutable|null                $paid_at
 * @property CarbonImmutable|null                $shipped_at
 * @property CarbonImmutable|null                $delivered_at
 * @property CarbonImmutable|null                $cancelled_at
 * @property CarbonImmutable|null                $created_at
 * @property CarbonImmutable|null                $updated_at
 * @property User|null                           $user
 * @property Coupon|null                         $coupon
 * @property Collection<int, OrderItem>          $items
 * @property Collection<int, Payment>            $payments
 * @property Payment|null                        $latestPayment
 * @property Collection<int, OrderStatusHistory> $statusHistories
 * @property int|null                            $items_count
 */
#[Fillable([
    'number',
    'user_id',
    'coupon_id',
    'status',
    'payment_status',
    'payment_method',
    'currency',
    'subtotal',
    'discount_total',
    'shipping_total',
    'tax_total',
    'grand_total',
    'coupon_code',
    'customer_email',
    'shipping_name',
    'shipping_phone',
    'shipping_line1',
    'shipping_line2',
    'shipping_city',
    'shipping_state',
    'shipping_postal_code',
    'shipping_country',
    'notes',
    'placed_at',
])]
#[RouteKey('number')]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

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
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    /**
     * Customers may cancel orders that have not been paid or processed yet.
     */
    public function isCancellableByCustomer(): bool
    {
        return $this->status === OrderStatus::Pending && !$this->isPaid();
    }

    /**
     * Whether an online payment is still outstanding for this order.
     */
    public function awaitsOnlinePayment(): bool
    {
        return $this->payment_method->isPrepaid()
            && $this->status === OrderStatus::Pending
            && in_array($this->payment_status, [PaymentStatus::Unpaid, PaymentStatus::Failed], true);
    }

    /**
     * @return list<string>
     */
    public function shippingAddressLines(): array
    {
        return array_values(array_filter([
            $this->shipping_line1,
            $this->shipping_line2,
            trim(implode(', ', array_filter([$this->shipping_city, $this->shipping_state, $this->shipping_postal_code]))),
            $this->shipping_country,
        ]));
    }

    /**
     * @param Builder<self> $query
     */
    #[Scope]
    protected function forUser(Builder $query, User $user): void
    {
        $query->where($query->qualifyColumn('user_id'), $user->getKey());
    }

    /**
     * Orders that represent a sale: not cancelled, and paid unless settled on delivery.
     *
     * @param Builder<self> $query
     */
    #[Scope]
    protected function countedInSales(Builder $query): void
    {
        $query->where($query->qualifyColumn('status'), '!=', OrderStatus::Cancelled)
            ->where(static function (Builder $query): void {
                $query->where($query->qualifyColumn('payment_method'), PaymentMethod::CashOnDelivery)
                    ->orWhere($query->qualifyColumn('payment_status'), PaymentStatus::Paid);
            });
    }

    /**
     * @param Builder<self> $query
     */
    #[Scope]
    protected function placedBetween(Builder $query, DateRange $range): void
    {
        $query->whereBetween($query->qualifyColumn('placed_at'), [$range->from, $range->to]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'         => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal'       => MoneyCast::class,
            'discount_total' => MoneyCast::class,
            'shipping_total' => MoneyCast::class,
            'tax_total'      => MoneyCast::class,
            'grand_total'    => MoneyCast::class,
            'placed_at'      => 'datetime',
            'paid_at'        => 'datetime',
            'shipped_at'     => 'datetime',
            'delivered_at'   => 'datetime',
            'cancelled_at'   => 'datetime',
        ];
    }
}
