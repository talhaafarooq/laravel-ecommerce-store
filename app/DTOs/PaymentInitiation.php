<?php

declare(strict_types = 1);

namespace App\DTOs;

/**
 * The outcome of starting a payment with a gateway.
 */
final readonly class PaymentInitiation
{
    public function __construct(
        public ?string $redirectUrl = null,
        public ?string $reference = null,
    ) {}

    public function requiresRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }
}
