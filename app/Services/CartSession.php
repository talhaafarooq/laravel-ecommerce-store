<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\CartRepositoryInterface;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Resolves the cart that belongs to the current web request.
 *
 * Authenticated customers use their own cart; guests are identified by a
 * random token kept in their session, which survives session regeneration.
 */
final readonly class CartSession
{
    public const string TOKEN_KEY = 'cart_token';

    public function __construct(private CartRepositoryInterface $carts) {}

    public function current(Request $request): ?Cart
    {
        $user = $request->user();

        if ($user instanceof User) {
            return $this->carts->findForUser($user);
        }

        $token = $this->guestToken($request);

        return $token === null ? null : $this->carts->findByToken($token);
    }

    public function currentOrCreate(Request $request): Cart
    {
        $user = $request->user();

        if ($user instanceof User) {
            return $this->carts->firstOrCreateForUser($user);
        }

        $token = $this->guestToken($request);

        if ($token !== null && ($cart = $this->carts->findByToken($token)) !== null) {
            return $cart;
        }

        $token = Str::random(40);
        $request->session()->put(self::TOKEN_KEY, $token);

        return $this->carts->createForToken($token);
    }

    public function guestToken(Request $request): ?string
    {
        if (!$request->hasSession()) {
            return null;
        }

        $token = $request->session()->get(self::TOKEN_KEY);

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function forgetGuestToken(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->forget(self::TOKEN_KEY);
        }
    }
}
