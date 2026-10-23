<?php

declare(strict_types = 1);

namespace App\Listeners;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Events\ProductStockLow;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Notification;

final readonly class NotifyAdminsOfLowStock
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function handle(ProductStockLow $event): void
    {
        $admins = $this->users->activeAdmins();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new LowStockNotification($event->product));
        }
    }
}
