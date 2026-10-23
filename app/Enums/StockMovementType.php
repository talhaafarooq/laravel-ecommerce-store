<?php

declare(strict_types = 1);

namespace App\Enums;

use App\Contracts\HasLabel;
use App\Enums\Concerns\EnumOptions;

enum StockMovementType: string implements HasLabel
{
    use EnumOptions;

    case Initial    = 'initial';
    case Sale       = 'sale';
    case Return     = 'return';
    case Restock    = 'restock';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Initial    => 'Opening stock',
            self::Sale       => 'Sale',
            self::Return     => 'Order cancelled (returned)',
            self::Restock    => 'Restock',
            self::Adjustment => 'Manual adjustment',
        };
    }

    /**
     * The movement types an administrator can record manually.
     *
     * @return list<self>
     */
    public static function manual(): array
    {
        return [self::Restock, self::Adjustment];
    }
}
