<?php

declare(strict_types = 1);

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Exceptions\PaymentException;

/**
 * Resolves the gateway responsible for a payment method.
 */
final readonly class PaymentGatewayManager
{
    /**
     * @param iterable<PaymentGateway> $gateways
     */
    public function __construct(private iterable $gateways) {}

    /**
     * @throws PaymentException
     */
    public function for(PaymentMethod $method): PaymentGateway
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->method() === $method && $gateway->isAvailable()) {
                return $gateway;
            }
        }

        throw PaymentException::gatewayUnavailable();
    }

    public function isAvailable(PaymentMethod $method): bool
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->method() === $method) {
                return $gateway->isAvailable();
            }
        }

        return false;
    }

    /**
     * @return list<PaymentMethod>
     */
    public function availableMethods(): array
    {
        return array_values(array_filter(
            PaymentMethod::cases(),
            fn (PaymentMethod $method): bool => $this->isAvailable($method),
        ));
    }
}
