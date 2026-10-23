<?php

declare(strict_types = 1);

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Notifications\OrderStatusUpdatedNotification;

final class SendOrderStatusNotification
{
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->to === OrderStatus::Pending) {
            return;
        }

        $order = $event->order->loadMissing('user');

        $order->user?->notify(new OrderStatusUpdatedNotification($order, $event->to));
    }
}
