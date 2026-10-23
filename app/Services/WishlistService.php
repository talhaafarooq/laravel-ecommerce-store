<?php

declare(strict_types = 1);

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\ProductUnavailableException;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class WishlistService
{
    public function __construct(private CartService $cart) {}

    /**
     * Add the product when missing, otherwise remove it. Returns whether it is now saved.
     */
    public function toggle(User $user, Product $product): bool
    {
        $deleted = $user->wishlistItems()->where('product_id', $product->id)->delete();

        if ($deleted > 0) {
            return false;
        }

        $this->add($user, $product);

        return true;
    }

    /**
     * @throws ProductUnavailableException
     */
    public function add(User $user, Product $product): void
    {
        if (!$product->is_active || $product->trashed()) {
            throw ProductUnavailableException::for($product);
        }

        Wishlist::query()->firstOrCreate(['user_id' => $user->id, 'product_id' => $product->id]);
    }

    public function remove(User $user, Product $product): void
    {
        $user->wishlistItems()->where('product_id', $product->id)->delete();
    }

    /**
     * @return list<int>
     */
    public function productIds(User $user): array
    {
        return array_values($user->wishlistItems()
            ->pluck('product_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all());
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return Product::query()
            ->whereIn('id', $user->wishlistItems()->select('product_id'))
            ->whereHas('category', static fn (Builder $query) => $query->where('is_active', true))
            ->where('is_active', true)
            ->with(['primaryImage', 'brand:id,name,slug', 'category:id,name,slug'])
            ->latest('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @throws ProductUnavailableException|InsufficientStockException
     */
    public function moveToCart(User $user, Product $product, Cart $cart): void
    {
        $this->cart->addItem($cart, $product->id);
        $this->remove($user, $product);
    }
}
