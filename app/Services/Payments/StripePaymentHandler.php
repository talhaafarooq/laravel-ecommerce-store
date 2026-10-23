<?php

declare(strict_types = 1);

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Order;
use App\Models\Payment;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;

/**
 * Reconciles Stripe Checkout sessions with local payments.
 *
 * Both the customer's return from Stripe and the webhook converge here, and
 * every state change is idempotent, so they can arrive in any order.
 */
final readonly class StripePaymentHandler
{
    public function __construct(
        private StripeApi $stripe,
        private PaymentService $payments,
    ) {}

    /**
     * Confirm the session the customer returned with. Returns whether it is paid.
     */
    public function confirmReturn(Order $order, string $sessionId): bool
    {
        $payment = $this->payments->findByReference(PaymentMethod::Stripe, $sessionId);

        if ($payment === null || $payment->order_id !== $order->id) {
            return false;
        }

        if ($payment->status === TransactionStatus::Succeeded) {
            return true;
        }

        try {
            $session = $this->stripe->retrieveCheckoutSession($sessionId);
        } catch (ApiErrorException $exception) {
            report($exception);

            return false;
        }

        return $this->apply($payment, $session);
    }

    /**
     * The customer abandoned the hosted checkout page.
     */
    public function cancelReturn(Order $order): void
    {
        $payment = $order->payments()
            ->where('gateway', PaymentMethod::Stripe)
            ->where('status', TransactionStatus::Pending)
            ->latest('id')
            ->first();

        if ($payment !== null) {
            $this->payments->markFailed($payment, 'The customer cancelled the payment.');
        }
    }

    /**
     * Handle a verified webhook event.
     */
    public function handleWebhook(Event $event): void
    {
        $session = $event->data->object;

        if (!$session instanceof Session) {
            return;
        }

        $payment = $this->payments->findByReference(PaymentMethod::Stripe, $session->id);

        if ($payment === null) {
            return;
        }

        match ($event->type) {
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' => $this->apply($payment, $session),
            'checkout.session.async_payment_failed'    => $this->payments->markFailed($payment, 'The payment was declined.', $this->payload($session)),
            'checkout.session.expired'                 => $this->payments->markFailed($payment, 'The checkout session expired.', $this->payload($session)),
            default                                    => null,
        };
    }

    /**
     * Apply the state of a session to the payment. Returns whether it is paid.
     */
    private function apply(Payment $payment, Session $session): bool
    {
        if ($session->status === 'expired') {
            $this->payments->markFailed($payment, 'The checkout session expired.', $this->payload($session));

            return false;
        }

        if (!in_array($session->payment_status, ['paid', 'no_payment_required'], true)) {
            // Asynchronous payment methods settle later through the webhook.
            return false;
        }

        $currencyMatches = strtoupper((string) $session->currency) === $payment->currency;

        if ($session->amount_total !== $payment->amount->amount || !$currencyMatches) {
            report(new RuntimeException("Stripe session {$session->id} amount does not match payment {$payment->id}."));
            $this->payments->markFailed($payment, 'The paid amount did not match the order total.', $this->payload($session));

            return false;
        }

        $this->payments->markSucceeded($payment, $this->payload($session));

        return true;
    }

    /**
     * The non-sensitive session details kept for reconciliation.
     *
     * @return array<string, mixed>
     */
    private function payload(Session $session): array
    {
        $intent = $session->payment_intent;

        return [
            'session_id'     => $session->id,
            'payment_intent' => is_string($intent) ? $intent : $intent?->id,
            'payment_status' => $session->payment_status,
            'amount_total'   => $session->amount_total,
            'currency'       => $session->currency,
        ];
    }
}
