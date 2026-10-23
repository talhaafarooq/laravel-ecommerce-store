<?php

declare(strict_types = 1);

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public OrderStatus $status,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $message] = match ($this->status) {
            OrderStatus::Processing => ['is being prepared', 'Good news! We are now preparing your order for shipment.'],
            OrderStatus::Shipped    => ['has shipped', 'Your order is on its way and should arrive soon.'],
            OrderStatus::Delivered  => ['was delivered', 'Your order has been delivered. We hope you love it! Reviews help other shoppers, so tell us what you think.'],
            OrderStatus::Cancelled  => ['was cancelled', 'Your order has been cancelled.' . ($this->order->cancellation_reason ? " Reason: {$this->order->cancellation_reason}" : '')],
            OrderStatus::Pending    => ['is pending', 'Your order is awaiting confirmation.'],
        };

        return (new MailMessage)
            ->subject("Your order {$this->order->number} {$subject}")
            ->greeting("Hi {$this->order->shipping_name},")
            ->line($message)
            ->line("Order total: {$this->order->grand_total->format()}")
            ->action('View your order', route('account.orders.show', $this->order))
            ->line('Thank you for shopping with us.');
    }
}
