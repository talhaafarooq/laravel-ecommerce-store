<?php

declare(strict_types = 1);

namespace App\Exceptions;

final class OrderNotCancellableException extends BusinessRuleException
{
    public function __construct(string $message = 'This order can no longer be cancelled. Please contact support for help.')
    {
        parent::__construct($message);
    }

    public function errorKey(): string
    {
        return 'order';
    }
}
