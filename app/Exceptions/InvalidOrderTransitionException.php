<?php

declare(strict_types = 1);

namespace App\Exceptions;

use App\Enums\OrderStatus;

final class InvalidOrderTransitionException extends BusinessRuleException
{
    public static function between(OrderStatus $from, OrderStatus $to): self
    {
        return new self("An order that is {$from->label()} cannot be marked as {$to->label()}.");
    }

    public function errorKey(): string
    {
        return 'status';
    }
}
