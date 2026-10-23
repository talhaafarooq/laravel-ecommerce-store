<?php

declare(strict_types = 1);

namespace App\Enums;

use App\Contracts\HasLabel;
use App\Enums\Concerns\EnumOptions;

enum OrderStatus: string implements HasLabel
{
    use EnumOptions;

    case Pending    = 'pending';
    case Processing = 'processing';
    case Shipped    = 'shipped';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::Processing => 'Processing',
            self::Shipped    => 'Shipped',
            self::Delivered  => 'Delivered',
            self::Cancelled  => 'Cancelled',
        };
    }

    /**
     * The visual tone used by status badges.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending    => 'warning',
            self::Processing => 'info',
            self::Shipped    => 'primary',
            self::Delivered  => 'success',
            self::Cancelled  => 'danger',
        };
    }

    /**
     * The statuses an order may move to from the current status.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending                    => [self::Processing, self::Cancelled],
            self::Processing                 => [self::Shipped, self::Cancelled],
            self::Shipped                    => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * The position of the status in the fulfilment timeline.
     */
    public function step(): int
    {
        return match ($this) {
            self::Pending    => 1,
            self::Processing => 2,
            self::Shipped    => 3,
            self::Delivered  => 4,
            self::Cancelled  => 0,
        };
    }
}
