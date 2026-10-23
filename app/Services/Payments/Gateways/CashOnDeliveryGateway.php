<?php

declare(strict_types = 1);

namespace App\Services\Payments\Gateways;

use App\Contracts\Payments\PaymentGateway;
use App\DTOs\PaymentInitiation;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;

/**
 * Payment is collected by the courier, so nothing happens online.
 */
final class CashOnDeliveryGateway implements PaymentGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::CashOnDelivery;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function initiate(Order $order, Payment $payment): PaymentInitiation
    {
        return new PaymentInitiation;
    }
}
