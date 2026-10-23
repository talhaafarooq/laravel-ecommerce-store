<?php

declare(strict_types = 1);

namespace App\Repositories;

use App\Contracts\Repositories\CartRepositoryInterface;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;

final class CartRepository implements CartRepositoryInterface
{
    public function findForUser(User $user): ?Cart
    {
        return Cart::query()->where('user_id', $user->getKey())->first();
    }

    public function findByToken(string $token): ?Cart
    {
        return Cart::query()->whereNull('user_id')->where('token', $token)->first();
    }

    public function firstOrCreateForUser(User $user): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $user->getKey()]);
    }

    public function createForToken(string $token): Cart
    {
        return Cart::query()->create(['token' => $token]);
    }

    public function loadForPricing(Cart $cart): Cart
    {
        return $cart->load([
            'coupon',
            'items.product' => static fn ($query) => $query->with(['primaryImage', 'category:id,name,slug,is_active']),
        ]);
    }

    public function findItem(Cart $cart, int $productId): ?CartItem
    {
        return $cart->items()->where('product_id', $productId)->first();
    }

    public function delete(Cart $cart): void
    {
        $cart->delete();
    }
}
