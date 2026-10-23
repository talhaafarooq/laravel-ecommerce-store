<?php

declare(strict_types = 1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment attempt for an order through a specific gateway.
 *
 * @property int                       $id
 * @property int                       $order_id
 * @property PaymentMethod             $gateway
 * @property TransactionStatus         $status
 * @property Money                     $amount
 * @property string                    $currency
 * @property string|null               $reference
 * @property array<string, mixed>|null $payload
 * @property string|null               $failure_reason
 * @property CarbonImmutable|null      $paid_at
 * @property CarbonImmutable|null      $created_at
 * @property CarbonImmutable|null      $updated_at
 * @property Order                     $order
 */
#[Fillable(['gateway', 'status', 'amount', 'currency', 'reference', 'payload', 'failure_reason', 'paid_at'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::Pending;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gateway' => PaymentMethod::class,
            'status'  => TransactionStatus::class,
            'amount'  => MoneyCast::class,
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }
}
