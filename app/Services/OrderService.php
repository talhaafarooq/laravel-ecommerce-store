<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Events\OrderStatusChanged;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\OrderNotCancellableException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Applies order lifecycle transitions and their side effects.
 */
final readonly class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private InventoryService $inventory,
        private CouponService $coupons,
        private Dispatcher $events,
    ) {}

    /**
     * Move an order to a new status on behalf of an administrator.
     *
     * @throws InvalidOrderTransitionException
     */
    public function updateStatus(Order $order, OrderStatus $status, ?string $note = null, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $status, $note, $actor): Order {
            $locked = $this->orders->lockForUpdate($order);

            $this->transition($locked, $status, $note, $actor);

            return $locked;
        });
    }

    /**
     * Cancel an order at the customer's request.
     *
     * @throws OrderNotCancellableException
     */
    public function cancelByCustomer(Order $order, User $customer, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $customer, $reason): Order {
            $locked = $this->orders->lockForUpdate($order);

            if (!$locked->isCancellableByCustomer()) {
                throw new OrderNotCancellableException;
            }

            $this->transition($locked, OrderStatus::Cancelled, $reason ?: 'Cancelled by the customer.', $customer);

            return $locked;
        });
    }

    /**
     * Cancel an online order whose payment never completed, releasing its stock.
     */
    public function cancelUnpaid(Order $order, string $reason = 'Payment was not completed in time.'): void
    {
        DB::transaction(function () use ($order, $reason): void {
            $locked = $this->orders->lockForUpdate($order);

            if (!$locked->awaitsOnlinePayment()) {
                return;
            }

            $this->transition($locked, OrderStatus::Cancelled, $reason);
        });
    }

    /**
     * Apply a status transition. The order must be locked by the caller.
     *
     * @throws InvalidOrderTransitionException
     */
    public function transition(Order $order, OrderStatus $to, ?string $note = null, ?User $actor = null): void
    {
        $from = $order->status;

        if ($from === $to) {
            return;
        }

        if (!$from->canTransitionTo($to)) {
            throw InvalidOrderTransitionException::between($from, $to);
        }

        $order->status = $to;

        match ($to) {
            OrderStatus::Shipped   => $order->shipped_at = now(),
            OrderStatus::Delivered => $this->markDelivered($order),
            OrderStatus::Cancelled => $this->markCancelled($order, $note, $actor),
            default                => null,
        };

        $order->save();

        $order->statusHistories()->create([
            'user_id'     => $actor?->getKey(),
            'from_status' => $from,
            'to_status'   => $to,
            'note'        => $note,
        ]);

        $this->events->dispatch(new OrderStatusChanged($order, $from, $to));
    }

    /**
     * Cash on delivery orders are settled when the parcel is delivered.
     */
    private function markDelivered(Order $order): void
    {
        $order->delivered_at = now();
        $order->shipped_at ??= now();

        if ($order->payment_method === PaymentMethod::CashOnDelivery && !$order->isPaid()) {
            $order->payment_status = PaymentStatus::Paid;
            $order->paid_at        = now();

            $order->payments()
                ->where('status', TransactionStatus::Pending)
                ->update(['status' => TransactionStatus::Succeeded, 'paid_at' => now()]);
        }
    }

    private function markCancelled(Order $order, ?string $reason, ?User $actor): void
    {
        $order->cancelled_at        = now();
        $order->cancellation_reason = $reason;

        $this->inventory->restockOrder($order, $actor);

        $order->loadMissing('coupon');

        if ($order->coupon !== null) {
            $this->coupons->release($order->coupon);
        }

        if (!$order->isPaid()) {
            $order->payments()
                ->where('status', TransactionStatus::Pending)
                ->update(['status' => TransactionStatus::Failed, 'failure_reason' => 'Order cancelled']);
        }
    }
}
