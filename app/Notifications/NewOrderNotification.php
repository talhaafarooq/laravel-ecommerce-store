<?php

declare(strict_types = 1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the store team that a new order needs attention.
 */
final class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
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
        $this->order->loadCount('items');

        return (new MailMessage)
            ->subject("New order {$this->order->number} ({$this->order->grand_total->format()})")
            ->greeting('A new order was placed')
            ->line("Order **{$this->order->number}** from {$this->order->shipping_name} ({$this->order->customer_email}).")
            ->line("Items: {$this->order->items_count} · Total: {$this->order->grand_total->format()} · Payment: {$this->order->payment_method->label()}")
            ->action('Review the order', route('admin.orders.show', $this->order));
    }
}
