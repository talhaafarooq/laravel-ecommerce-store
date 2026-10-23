<?php

declare(strict_types = 1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

final class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $this->owns($user, $order) || $user->isAdmin();
    }

    public function downloadInvoice(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->owns($user, $order) && $order->isCancellableByCustomer();
    }

    public function pay(User $user, Order $order): bool
    {
        return $this->owns($user, $order) && $order->awaitsOnlinePayment();
    }

    private function owns(User $user, Order $order): bool
    {
        return $order->user_id !== null && $order->user_id === $user->id;
    }
}
