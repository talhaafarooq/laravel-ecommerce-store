<?php

declare(strict_types = 1);

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\Coupon;
use App\Models\Order;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Support\Facades\Notification;

it('requires an authenticated, verified customer', function (): void {
    $this->get(route('checkout.show'))->assertRedirect(route('login'));
    $this->actingAs(createCustomer(['email_verified_at' => null]))->get(route('checkout.show'))->assertRedirect(route('verification.notice'));
});

it('sends customers with an empty cart back to the cart', function (): void {
    $this->actingAs(createCustomer())->get(route('checkout.show'))->assertRedirect(route('cart.index'));
    $this->actingAs(createCustomer())->postJson(route('checkout.store'), checkoutPayload())->assertUnprocessable()->assertJsonValidationErrorFor('cart');
});

it('places a cash on delivery order, reserves stock and empties the cart', function (): void {
    Notification::fake();
    $admin   = createAdmin();
    $user    = createCustomer();
    $product = createProduct(['price' => 3000, 'stock_quantity' => 10]);
    $cart    = cartWith($user, [$product->id => 2]);

    $response = $this->actingAs($user)->post(route('checkout.store'), checkoutPayload(['notes' => 'Leave at the door']));

    $order = Order::query()->firstOrFail();
    $response->assertRedirect(route('checkout.complete', $order));

    expect($order->user_id)->toBe($user->id)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($order->subtotal->amount)->toBe(6000)
        ->and($order->shipping_total->amount)->toBe(0)
        ->and($order->grand_total->amount)->toBe(6000)
        ->and($order->shipping_city)->toBe('Springfield')
        ->and($order->notes)->toBe('Leave at the door')
        ->and($order->number)->toStartWith('ORD-')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->product_name)->toBe($product->name)
        ->and($product->fresh()->stock_quantity)->toBe(8)
        ->and($product->stockMovements()->where('type', StockMovementType::Sale)->value('quantity'))->toBe(-2)
        ->and($cart->items()->count())->toBe(0);

    Notification::assertSentTo($user, OrderPlacedNotification::class);
    Notification::assertSentTo($admin, NewOrderNotification::class);
});

it('snapshots prices so later catalogue changes do not alter the order', function (): void {
    $user    = createCustomer();
    $product = createProduct(['price' => 4000, 'name' => 'Original Name']);
    cartWith($user, [$product->id => 1]);

    $this->actingAs($user)->post(route('checkout.store'), checkoutPayload());
    $product->update(['price' => 9900, 'name' => 'Renamed']);

    $item = Order::query()->firstOrFail()->items->first();
    expect($item->unit_price->amount)->toBe(4000)->and($item->product_name)->toBe('Original Name');
});

it('charges shipping below the free shipping threshold', function (): void {
    $user    = createCustomer();
    $product = createProduct(['price' => 2000]);
    cartWith($user, [$product->id => 1]);

    $this->actingAs($user)->post(route('checkout.store'), checkoutPayload());

    expect(Order::query()->firstOrFail()->grand_total->amount)->toBe(2500);
});

it('fails atomically when stock ran out after the item was carted', function (): void {
    $user    = createCustomer();
    $product = createProduct(['stock_quantity' => 5]);
    $cart    = cartWith($user, [$product->id => 3]);
    $product->update(['stock_quantity' => 2]);

    $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload())
        ->assertStatus(409)->assertJsonValidationErrorFor('quantity');

    expect(Order::query()->count())->toBe(0)
        ->and($product->fresh()->stock_quantity)->toBe(2)
        ->and($cart->items()->count())->toBe(1);
});

it('never oversells when two checkouts compete for the last unit', function (): void {
    $product = createProduct(['stock_quantity' => 1]);
    $first   = createCustomer();
    $second  = createCustomer();
    cartWith($first, [$product->id => 1]);
    cartWith($second, [$product->id => 1]);

    $this->actingAs($first)->post(route('checkout.store'), checkoutPayload())->assertRedirect();
    $this->actingAs($second)->postJson(route('checkout.store'), checkoutPayload())->assertUnprocessable();

    expect(Order::query()->count())->toBe(1)->and($product->fresh()->stock_quantity)->toBe(0);
});

it('rejects products deactivated since they were carted', function (): void {
    $user    = createCustomer();
    $product = createProduct();
    cartWith($user, [$product->id => 1]);
    $product->update(['is_active' => false]);

    $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload())->assertUnprocessable();

    expect(Order::query()->count())->toBe(0);
});

it('validates the shipping address and payment method', function (): void {
    $user = createCustomer();
    cartWith($user, [createProduct()->id => 1]);

    $this->actingAs($user)->postJson(route('checkout.store'), [
        'shipping'       => ['full_name' => '', 'phone' => 'abc', 'country' => 'USA'],
        'payment_method' => 'bitcoin',
    ])->assertJsonValidationErrors([
        'shipping.full_name', 'shipping.phone', 'shipping.line1', 'shipping.city',
        'shipping.postal_code', 'shipping.country', 'payment_method',
    ]);
});

it('rejects stripe when it is not configured', function (): void {
    $user = createCustomer();
    cartWith($user, [createProduct()->id => 1]);

    $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload(['payment_method' => 'stripe']))->assertStatus(402);

    expect(Order::query()->count())->toBe(0);
});

it('redeems a coupon exactly once per order and releases it on cancellation', function (): void {
    $user    = createCustomer();
    $coupon  = Coupon::factory()->create(['code' => 'TEN', 'value' => 10, 'usage_limit' => 5]);
    $product = createProduct(['price' => 10000]);
    cartWith($user, [$product->id => 1]);
    $this->actingAs($user)->post(route('cart.coupon.store'), ['code' => 'TEN']);

    $this->actingAs($user)->post(route('checkout.store'), checkoutPayload())->assertRedirect();

    $order = Order::query()->firstOrFail();
    expect($order->discount_total->amount)->toBe(1000)
        ->and($order->grand_total->amount)->toBe(9000)
        ->and($order->coupon_code)->toBe('TEN')
        ->and($coupon->fresh()->times_used)->toBe(1);

    $this->actingAs($user)->post(route('account.orders.cancel', $order))->assertRedirect();

    expect($coupon->fresh()->times_used)->toBe(0)->and($product->fresh()->stock_quantity)->toBe($product->stock_quantity);
});
