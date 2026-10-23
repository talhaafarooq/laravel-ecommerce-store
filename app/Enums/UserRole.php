<?php

declare(strict_types = 1);

namespace App\Enums;

use App\Contracts\HasLabel;
use App\Enums\Concerns\EnumOptions;

enum UserRole: string implements HasLabel
{
    use EnumOptions;

    case Admin    = 'admin';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin    => 'Administrator',
            self::Customer => 'Customer',
        };
    }
}
