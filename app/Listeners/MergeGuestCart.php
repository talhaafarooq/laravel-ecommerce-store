<?php

declare(strict_types = 1);

namespace App\Listeners;

use App\Models\User;
use App\Services\CartService;
use App\Services\CartSession;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * Keeps the items a shopper added before logging in.
 */
final readonly class MergeGuestCart
{
    public function __construct(
        private CartSession $cartSession,
        private CartService $carts,
        private Request $request,
    ) {}

    public function handle(Login $event): void
    {
        if (!$event->user instanceof User) {
            return;
        }

        $token = $this->cartSession->guestToken($this->request);

        if ($token === null) {
            return;
        }

        $this->carts->mergeGuestCart($token, $event->user);
        $this->cartSession->forgetGuestToken($this->request);
    }
}
