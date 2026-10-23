<?php

declare(strict_types = 1);

namespace App\Listeners;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Events\OrderPlaced;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Support\Facades\Notification;

final readonly class SendOrderPlacedNotifications
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->loadMissing('user');

        $order->user?->notify(new OrderPlacedNotification($order));

        $admins = $this->users->activeAdmins();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewOrderNotification($order));
        }
    }
}
