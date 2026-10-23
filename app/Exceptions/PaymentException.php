<?php

declare(strict_types = 1);

namespace App\Exceptions;

use Throwable;

/**
 * A payment gateway could not complete a request.
 *
 * The customer facing message is generic; the original exception is kept as
 * the previous exception so the details are available in the logs.
 */
final class PaymentException extends BusinessRuleException
{
    protected bool $shouldLog = true;

    public static function gatewayUnavailable(): self
    {
        return new self('This payment method is currently unavailable. Please choose another one.');
    }

    public static function failedToStart(?Throwable $previous = null): self
    {
        return new self('We could not start your payment. Please try again or choose another payment method.', 0, $previous);
    }

    public static function verificationFailed(?Throwable $previous = null): self
    {
        return new self('We could not verify your payment. If you were charged, please contact support.', 0, $previous);
    }

    public function errorKey(): string
    {
        return 'payment_method';
    }

    public function status(): int
    {
        return 402;
    }
}
