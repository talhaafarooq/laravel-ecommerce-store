<?php

declare(strict_types = 1);

namespace App\Enums;

use App\Contracts\HasLabel;
use App\Enums\Concerns\EnumOptions;

enum PaymentMethod: string implements HasLabel
{
    use EnumOptions;

    case CashOnDelivery = 'cod';
    case Stripe         = 'stripe';

    public function label(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Cash on Delivery',
            self::Stripe         => 'Credit / Debit Card',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Pay with cash when your order is delivered.',
            self::Stripe         => 'Pay securely online with Stripe. Your card details never touch our servers.',
        };
    }

    /**
     * Whether the customer pays before the order is fulfilled.
     */
    public function isPrepaid(): bool
    {
        return $this === self::Stripe;
    }
}
