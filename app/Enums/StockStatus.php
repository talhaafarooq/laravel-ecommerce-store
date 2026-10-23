<?php

declare(strict_types = 1);

namespace App\Enums;

use App\Contracts\HasLabel;
use App\Enums\Concerns\EnumOptions;

enum StockStatus: string implements HasLabel
{
    use EnumOptions;

    case InStock    = 'in_stock';
    case LowStock   = 'low_stock';
    case OutOfStock = 'out_of_stock';

    public static function for(int $quantity, int $lowStockThreshold): self
    {
        return match (true) {
            $quantity <= 0                  => self::OutOfStock,
            $quantity <= $lowStockThreshold => self::LowStock,
            default                         => self::InStock,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::InStock    => 'In stock',
            self::LowStock   => 'Low stock',
            self::OutOfStock => 'Out of stock',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::InStock    => 'success',
            self::LowStock   => 'warning',
            self::OutOfStock => 'danger',
        };
    }
}
