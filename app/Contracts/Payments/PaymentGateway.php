<?php

declare(strict_types = 1);

namespace App\Contracts\Payments;

use App\DTOs\PaymentInitiation;
use App\Enums\PaymentMethod;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Payment;

interface PaymentGateway
{
    /**
     * The payment method handled by the gateway.
     */
    public function method(): PaymentMethod;

    /**
     * Whether the gateway is configured and can accept payments.
     */
    public function isAvailable(): bool;

    /**
     * Start collecting the payment for an order.
     *
     * @throws PaymentException
     */
    public function initiate(Order $order, Payment $payment): PaymentInitiation;
}
