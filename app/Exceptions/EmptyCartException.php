<?php

declare(strict_types = 1);

namespace App\Exceptions;

final class EmptyCartException extends BusinessRuleException
{
    public function __construct(string $message = 'Your cart is empty.')
    {
        parent::__construct($message);
    }

    public function errorKey(): string
    {
        return 'cart';
    }
}
