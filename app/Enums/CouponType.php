<?php

declare(strict_types = 1);

namespace App\Enums;

use App\Contracts\HasLabel;
use App\Enums\Concerns\EnumOptions;

enum CouponType: string implements HasLabel
{
    use EnumOptions;

    case Percentage = 'percentage';
    case Fixed      = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage',
            self::Fixed      => 'Fixed amount',
        };
    }
}
