<?php

declare(strict_types = 1);

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product)
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
        $quantity = $this->product->stock_quantity;

        return (new MailMessage)
            ->subject("Low stock: {$this->product->name}")
            ->greeting('Inventory alert')
            ->line($quantity === 0
                ? "**{$this->product->name}** (SKU {$this->product->sku}) is now out of stock."
                : "**{$this->product->name}** (SKU {$this->product->sku}) has only {$quantity} unit(s) left.")
            ->action('Manage inventory', route('admin.inventory.index', ['search' => $this->product->sku]));
    }
}
