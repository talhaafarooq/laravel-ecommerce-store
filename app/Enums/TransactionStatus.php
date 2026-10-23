<?php

declare(strict_types = 1);

namespace App\Enums;

use App\Contracts\HasLabel;
use App\Enums\Concerns\EnumOptions;

enum TransactionStatus: string implements HasLabel
{
    use EnumOptions;

    case Pending   = 'pending';
    case Succeeded = 'succeeded';
    case Failed    = 'failed';
    case Refunded  = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending',
            self::Succeeded => 'Succeeded',
            self::Failed    => 'Failed',
            self::Refunded  => 'Refunded',
        };
    }

    public function isSettled(): bool
    {
        return $this !== self::Pending;
    }
}
