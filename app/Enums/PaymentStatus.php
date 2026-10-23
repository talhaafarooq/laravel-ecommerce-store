<?php

declare(strict_types = 1);

namespace App\Enums;

use App\Contracts\HasLabel;
use App\Enums\Concerns\EnumOptions;

enum PaymentStatus: string implements HasLabel
{
    use EnumOptions;

    case Unpaid   = 'unpaid';
    case Paid     = 'paid';
    case Failed   = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid   => 'Unpaid',
            self::Paid     => 'Paid',
            self::Failed   => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Unpaid   => 'warning',
            self::Paid     => 'success',
            self::Failed   => 'danger',
            self::Refunded => 'neutral',
        };
    }
}
