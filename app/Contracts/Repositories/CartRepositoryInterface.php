<?php

declare(strict_types = 1);

namespace App\Contracts\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;

interface CartRepositoryInterface
{
    public function findForUser(User $user): ?Cart;

    public function findByToken(string $token): ?Cart;

    public function firstOrCreateForUser(User $user): Cart;

    public function createForToken(string $token): Cart;

    /**
     * Eager load the items, products and coupon needed to price the cart.
     */
    public function loadForPricing(Cart $cart): Cart;

    public function findItem(Cart $cart, int $productId): ?CartItem;

    public function delete(Cart $cart): void;
}
