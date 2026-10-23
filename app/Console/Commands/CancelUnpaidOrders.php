<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Services\OrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Releases the stock held by online orders whose payment never completed.
 */
#[Signature('orders:cancel-unpaid {--minutes= : Override the configured payment window}')]
#[Description('Cancel online orders that were not paid within the payment window')]
final class CancelUnpaidOrders extends Command
{
    public function handle(OrderRepositoryInterface $orders, OrderService $orderService): int
    {
        $minutes = (int) ($this->option('minutes') ?? config('shop.orders.unpaid_ttl_minutes', 60));
        $stale   = $orders->unpaidOnlineOrdersPlacedBefore(now()->subMinutes(max(1, $minutes)));

        $cancelled = 0;

        foreach ($stale as $order) {
            try {
                $orderService->cancelUnpaid($order);
                $cancelled++;
            } catch (Throwable $exception) {
                report($exception);
                $this->components->error("Could not cancel order {$order->number}.");
            }
        }

        $this->components->info("Cancelled {$cancelled} unpaid order(s).");

        return self::SUCCESS;
    }
}
