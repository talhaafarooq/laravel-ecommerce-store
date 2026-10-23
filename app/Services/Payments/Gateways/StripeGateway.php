<?php

declare(strict_types = 1);

namespace App\Services\Payments\Gateways;

use App\Contracts\Payments\PaymentGateway;
use App\DTOs\PaymentInitiation;
use App\Enums\PaymentMethod;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\StripeApi;
use Stripe\Exception\ApiErrorException;

/**
 * Collects card payments through a hosted Stripe Checkout page.
 *
 * Card details are entered on Stripe's domain, keeping the store out of PCI scope.
 */
final readonly class StripeGateway implements PaymentGateway
{
    public function __construct(private StripeApi $stripe) {}

    public function method(): PaymentMethod
    {
        return PaymentMethod::Stripe;
    }

    public function isAvailable(): bool
    {
        return $this->stripe->isConfigured();
    }

    public function initiate(Order $order, Payment $payment): PaymentInitiation
    {
        $order->loadCount('items');

        $metadata = [
            'order_id'     => (string) $order->id,
            'order_number' => $order->number,
            'payment_id'   => (string) $payment->id,
        ];

        // Stripe requires checkout sessions to stay open for at least 30 minutes.
        $expiresInMinutes = max(30, min(1440, (int) config('shop.orders.unpaid_ttl_minutes', 60)));

        try {
            $session = $this->stripe->createCheckoutSession([
                'mode'                => 'payment',
                'client_reference_id' => (string) $order->id,
                'customer_email'      => $order->customer_email,
                'line_items'          => [[
                    'quantity'   => 1,
                    'price_data' => [
                        'currency'     => strtolower($order->currency),
                        'unit_amount'  => $order->grand_total->amount,
                        'product_data' => [
                            'name'        => config('app.name') . " order {$order->number}",
                            'description' => "{$order->items_count} item(s)",
                        ],
                    ],
                ]],
                'metadata'            => $metadata,
                'payment_intent_data' => ['metadata' => $metadata],
                'success_url'         => route('checkout.stripe.success', $order) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'          => route('checkout.stripe.cancel', $order),
                'expires_at'          => now()->addMinutes($expiresInMinutes)->getTimestamp(),
            ], "payment-{$payment->id}");
        } catch (ApiErrorException $exception) {
            throw PaymentException::failedToStart($exception);
        }

        if (!is_string($session->url) || $session->url === '') {
            throw PaymentException::failedToStart();
        }

        return new PaymentInitiation(redirectUrl: $session->url, reference: $session->id);
    }
}
