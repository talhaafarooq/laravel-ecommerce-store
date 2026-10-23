<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\DTOs\CheckoutData;
use App\DTOs\CheckoutResult;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidCouponException;
use App\Exceptions\PaymentException;
use App\Exceptions\ProductUnavailableException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use App\ValueObjects\Money;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Turns a cart into an order.
 */
final readonly class CheckoutService
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private ProductRepositoryInterface $products,
        private CartService $cartService,
        private CouponService $coupons,
        private InventoryService $inventory,
        private OrderTotalsCalculator $calculator,
        private OrderNumberGenerator $numbers,
        private AddressService $addresses,
        private PaymentGatewayManager $gateways,
        private PaymentService $payments,
        private Dispatcher $events,
    ) {}

    /**
     * Place the order and start collecting its payment.
     *
     * A payment that cannot be started does not undo the order: the customer
     * can retry from the order page and stale orders are cancelled later.
     *
     * @throws EmptyCartException|InsufficientStockException|ProductUnavailableException|InvalidCouponException|PaymentException
     */
    public function checkout(User $user, Cart $cart, CheckoutData $data): CheckoutResult
    {
        $order = $this->placeOrder($user, $cart, $data);

        try {
            return new CheckoutResult($order, $this->payments->initiate($order));
        } catch (PaymentException $exception) {
            report($exception);

            return new CheckoutResult($order, paymentError: $exception->getMessage());
        }
    }

    /**
     * Atomically create the order, reserve its stock and empty the cart.
     *
     * @throws EmptyCartException|InsufficientStockException|ProductUnavailableException|InvalidCouponException|PaymentException
     */
    public function placeOrder(User $user, Cart $cart, CheckoutData $data): Order
    {
        // Fail fast before touching the database when the method is unavailable.
        $this->gateways->for($data->paymentMethod);

        $order = DB::transaction(function () use ($user, $cart, $data): Order {
            $this->carts->loadForPricing($cart);

            if ($cart->items->isEmpty()) {
                throw new EmptyCartException;
            }

            $products = $this->products->lockForUpdate(
                $cart->items->pluck('product_id')->map(static fn (mixed $id): int => (int) $id)->all(),
            );

            $lines    = [];
            $subtotal = Money::zero();

            foreach ($cart->items as $item) {
                $product = $products->get($item->product_id);

                if (!$product instanceof Product || !$product->isPurchasable() || !$product->category->is_active) {
                    throw ProductUnavailableException::for($product ?? $item->product);
                }

                if ($item->quantity > $product->stock_quantity) {
                    throw InsufficientStockException::for($product, $item->quantity);
                }

                $lineTotal = $product->price->multiply($item->quantity);
                $subtotal  = $subtotal->add($lineTotal);
                $lines[]   = [$product, $item->quantity, $lineTotal];
            }

            $coupon   = $cart->coupon === null ? null : $this->coupons->redeem($cart->coupon, $subtotal, $user);
            $discount = $coupon       === null ? Money::zero() : $this->coupons->discountFor($coupon, $subtotal);
            $totals   = $this->calculator->calculate($subtotal, $discount);

            $order = Order::query()->create([
                'number'         => $this->numbers->generate(),
                'user_id'        => $user->id,
                'coupon_id'      => $coupon?->id,
                'coupon_code'    => $coupon?->code,
                'status'         => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_method' => $data->paymentMethod,
                'currency'       => $totals->total->currency,
                'subtotal'       => $totals->subtotal,
                'discount_total' => $totals->discount,
                'shipping_total' => $totals->shipping,
                'tax_total'      => $totals->tax,
                'grand_total'    => $totals->total,
                'customer_email' => $user->email,
                'notes'          => $data->notes,
                'placed_at'      => now(),
                ...$data->address->toOrderAttributes(),
            ]);

            foreach ($lines as [$product, $quantity, $lineTotal]) {
                $order->items()->create([
                    'product_id'    => $product->id,
                    'product_name'  => $product->name,
                    'product_sku'   => $product->sku,
                    'product_image' => $product->primaryImage->thumbnail_path ?? $product->primaryImage?->path,
                    'unit_price'    => $product->price,
                    'quantity'      => $quantity,
                    'line_total'    => $lineTotal,
                ]);

                $this->inventory->recordSale($product, $quantity, $order);
            }

            $order->statusHistories()->create([
                'user_id'   => $user->id,
                'to_status' => OrderStatus::Pending,
                'note'      => 'Order placed.',
            ]);

            if ($data->saveAddress) {
                $this->addresses->rememberFromCheckout($user, $data->address);
            }

            $this->cartService->clear($cart);

            return $order;
        });

        $this->events->dispatch(new OrderPlaced($order));

        return $order;
    }
}
