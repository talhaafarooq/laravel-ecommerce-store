<?php

declare(strict_types = 1);

namespace App\DTOs;

use App\Models\Order;

final readonly class CheckoutResult
{
    public function __construct(
        public Order $order,
        public ?PaymentInitiation $payment = null,
        public ?string $paymentError = null,
    ) {}

    public function redirectUrl(): ?string
    {
        return $this->payment?->redirectUrl;
    }
}
