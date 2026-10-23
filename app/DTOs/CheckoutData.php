<?php

declare(strict_types = 1);

namespace App\DTOs;

use App\Enums\PaymentMethod;

final readonly class CheckoutData
{
    public function __construct(
        public AddressData $address,
        public PaymentMethod $paymentMethod,
        public ?string $notes = null,
        public bool $saveAddress = false,
    ) {}
}
