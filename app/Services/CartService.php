<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\DTOs\CartLine;
use App\DTOs\CartSummary;
use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidCouponException;
use App\Exceptions\ProductUnavailableException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

final readonly class CartService
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private ProductRepositoryInterface $products,
        private CouponService $coupons,
        private OrderTotalsCalculator $calculator,
    ) {}

    /**
     * The cart of an authenticated user, created on demand.
     */
    public function forUser(User $user): Cart
    {
        return $this->carts->firstOrCreateForUser($user);
    }

    /**
     * Price the cart against the current state of its products.
     */
    public function summarize(?Cart $cart, ?User $user = null): CartSummary
    {
        if ($cart === null) {
            return new CartSummary(null, [], $this->calculator->calculate(Money::zero()));
        }

        $this->carts->loadForPricing($cart);

        $lines    = [];
        $subtotal = Money::zero();

        foreach ($cart->items as $item) {
            $product = $item->product;

            // Products that were archived since they were added are removed silently.
            if ($product === null) {
                $item->delete();

                continue;
            }

            $line = $this->priceLine($item, $product);

            if ($line->isAvailable()) {
                $subtotal = $subtotal->add($line->lineTotal);
            }

            $lines[] = $line;
        }

        [$coupon, $discount, $couponError] = $this->couponDiscount($cart->coupon, $subtotal, $user);

        return new CartSummary(
            cart: $cart,
            lines: $lines,
            totals: $this->calculator->calculate($subtotal, $discount),
            coupon: $coupon,
            couponError: $couponError,
        );
    }

    /**
     * Add a product to the cart, merging with an existing line.
     *
     * @throws ProductUnavailableException|InsufficientStockException
     */
    public function addItem(Cart $cart, int $productId, int $quantity = 1): CartItem
    {
        $product = $this->products->findVisible($productId);

        if ($product === null || !$product->isPurchasable()) {
            throw ProductUnavailableException::for($product);
        }

        $item     = $this->carts->findItem($cart, $product->id);
        $quantity = ($item->quantity ?? 0) + $quantity;

        $this->assertQuantityAllowed($product, $quantity);

        if ($item !== null) {
            $item->update(['quantity' => $quantity]);
        } else {
            $item = $cart->items()->create(['product_id' => $product->id, 'quantity' => $quantity]);
        }

        return $item->setRelation('product', $product);
    }

    /**
     * @throws ProductUnavailableException|InsufficientStockException
     */
    public function updateQuantity(Cart $cart, int $itemId, int $quantity): CartItem
    {
        $item = $cart->items()->with('product')->findOrFail($itemId);

        if ($quantity <= 0) {
            $item->delete();

            return $item;
        }

        $product = $item->product;

        if ($product === null || !$product->is_active) {
            throw ProductUnavailableException::for($product);
        }

        $this->assertQuantityAllowed($product, $quantity);

        $item->update(['quantity' => $quantity]);

        return $item;
    }

    public function removeItem(Cart $cart, int $itemId): void
    {
        $cart->items()->findOrFail($itemId)->delete();
    }

    public function clear(Cart $cart): void
    {
        DB::transaction(static function () use ($cart): void {
            $cart->items()->delete();
            $cart->forceFill(['coupon_id' => null])->save();
        });
    }

    /**
     * @throws InvalidCouponException|EmptyCartException
     */
    public function applyCoupon(Cart $cart, string $code, ?User $user): Coupon
    {
        $summary = $this->summarize($cart, $user);

        if ($summary->isEmpty()) {
            throw new EmptyCartException('Add items to your cart before applying a coupon.');
        }

        $coupon = $this->coupons->resolve($code, $summary->totals->subtotal, $user);

        $cart->coupon()->associate($coupon)->save();

        return $coupon;
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->coupon()->dissociate()->save();
    }

    /**
     * The number of units in the cart, without pricing it.
     */
    public function itemCount(?Cart $cart): int
    {
        return $cart === null ? 0 : (int) $cart->items()->sum('quantity');
    }

    /**
     * Move a guest cart into the user's cart after they log in.
     */
    public function mergeGuestCart(string $token, User $user): void
    {
        $guest = $this->carts->findByToken($token);

        if ($guest === null) {
            return;
        }

        $this->carts->loadForPricing($guest);

        DB::transaction(function () use ($guest, $user): void {
            $cart = $this->carts->firstOrCreateForUser($user);

            foreach ($guest->items as $item) {
                $product = $item->product;

                if ($product === null || !$product->isPurchasable()) {
                    continue;
                }

                $existing = $this->carts->findItem($cart, $product->id);
                $quantity = min(($existing->quantity ?? 0) + $item->quantity, $this->maxQuantityFor($product));

                if ($existing !== null) {
                    $existing->update(['quantity' => $quantity]);
                } else {
                    $cart->items()->create(['product_id' => $product->id, 'quantity' => $quantity]);
                }
            }

            if ($cart->coupon_id === null && $guest->coupon_id !== null) {
                $cart->forceFill(['coupon_id' => $guest->coupon_id])->save();
            }

            $this->carts->delete($guest);
        });
    }

    /**
     * The largest quantity of the product a single order may contain.
     */
    public function maxQuantityFor(Product $product): int
    {
        return max(0, min($product->stock_quantity, (int) config('shop.cart.max_quantity_per_item', 10)));
    }

    /**
     * @throws InsufficientStockException
     */
    private function assertQuantityAllowed(Product $product, int $quantity): void
    {
        if ($quantity > $product->stock_quantity) {
            throw InsufficientStockException::for($product, $quantity);
        }

        $limit = (int) config('shop.cart.max_quantity_per_item', 10);

        if ($quantity > $limit) {
            throw InsufficientStockException::perItemLimit($product, $limit);
        }
    }

    private function priceLine(CartItem $item, Product $product): CartLine
    {
        $issue = match (true) {
            !$product->is_active || !$product->category->is_active => 'This product is no longer available.',
            $product->stock_quantity <= 0                          => 'This product is out of stock.',
            $item->quantity > $product->stock_quantity             => "Only {$product->stock_quantity} left in stock. Please reduce the quantity.",
            default                                                => null,
        };

        return new CartLine(
            item: $item,
            product: $product,
            quantity: $item->quantity,
            unitPrice: $product->price,
            lineTotal: $product->price->multiply($item->quantity),
            maxQuantity: $this->maxQuantityFor($product),
            issue: $issue,
        );
    }

    /**
     * @return array{0: Coupon|null, 1: Money, 2: string|null}
     */
    private function couponDiscount(?Coupon $coupon, Money $subtotal, ?User $user): array
    {
        if ($coupon === null || $subtotal->isZero()) {
            return [$coupon, Money::zero(), null];
        }

        try {
            $this->coupons->assertApplicable($coupon, $subtotal, $user);
        } catch (InvalidCouponException $exception) {
            return [$coupon, Money::zero(), $exception->getMessage()];
        }

        return [$coupon, $this->coupons->discountFor($coupon, $subtotal), null];
    }
}
