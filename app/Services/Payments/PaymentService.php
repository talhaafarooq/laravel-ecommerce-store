<?php

declare(strict_types = 1);

namespace App\Services\Payments;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\DTOs\PaymentInitiation;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Starts payments and records their outcome against orders.
 */
final readonly class PaymentService
{
    public function __construct(
        private PaymentGatewayManager $gateways,
        private OrderRepositoryInterface $orders,
        private OrderService $orderService,
    ) {}

    /**
     * Start (or restart) collecting the payment for an order.
     *
     * @throws PaymentException
     */
    public function initiate(Order $order): PaymentInitiation
    {
        $gateway = $this->gateways->for($order->payment_method);

        // Each attempt gets its own record; earlier unfinished attempts are superseded.
        $order->payments()
            ->where('status', TransactionStatus::Pending)
            ->update(['status' => TransactionStatus::Failed, 'failure_reason' => 'Superseded by a new payment attempt.']);

        $payment = $order->payments()->create([
            'gateway'  => $order->payment_method,
            'status'   => TransactionStatus::Pending,
            'amount'   => $order->grand_total,
            'currency' => $order->currency,
        ]);

        $initiation = $gateway->initiate($order, $payment);

        if ($initiation->reference !== null) {
            $payment->update(['reference' => $initiation->reference]);
        }

        return $initiation;
    }

    public function findByReference(PaymentMethod $gateway, string $reference): ?Payment
    {
        return Payment::query()
            ->where('gateway', $gateway)
            ->where('reference', $reference)
            ->first();
    }

    /**
     * Record a successful payment. Safe to call more than once.
     *
     * @param array<string, mixed> $payload
     */
    public function markSucceeded(Payment $payment, array $payload = []): void
    {
        DB::transaction(function () use ($payment, $payload): void {
            [$order, $payment] = $this->lock($payment);

            if ($payment->status === TransactionStatus::Succeeded) {
                return;
            }

            $payment->update([
                'status'         => TransactionStatus::Succeeded,
                'paid_at'        => now(),
                'payload'        => $payload,
                'failure_reason' => null,
            ]);

            $order->forceFill(['payment_status' => PaymentStatus::Paid, 'paid_at' => now()])->save();

            if ($order->status === OrderStatus::Pending) {
                $this->orderService->transition($order, OrderStatus::Processing, 'Payment received online.');
            } elseif ($order->status === OrderStatus::Cancelled) {
                Log::warning('Payment received for a cancelled order; a refund is required.', [
                    'order'   => $order->number,
                    'payment' => $payment->id,
                ]);
            }
        });
    }

    /**
     * Record a failed payment attempt. The order stays open so the customer can retry.
     *
     * @param array<string, mixed> $payload
     */
    public function markFailed(Payment $payment, ?string $reason = null, array $payload = []): void
    {
        DB::transaction(function () use ($payment, $reason, $payload): void {
            [$order, $payment] = $this->lock($payment);

            if ($payment->status !== TransactionStatus::Pending) {
                return;
            }

            $payment->update([
                'status'         => TransactionStatus::Failed,
                'failure_reason' => $reason,
                'payload'        => $payload === [] ? $payment->payload : $payload,
            ]);

            if (!$order->isPaid()) {
                $order->forceFill(['payment_status' => PaymentStatus::Failed])->save();
            }
        });
    }

    /**
     * Lock the order before the payment, matching the order used by cancellations.
     *
     * @return array{0: Order, 1: Payment}
     */
    private function lock(Payment $payment): array
    {
        $payment->loadMissing('order');

        $order  = $this->orders->lockForUpdate($payment->order);
        $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

        return [$order, $locked];
    }
}
