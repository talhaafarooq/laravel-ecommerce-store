<?php

declare(strict_types = 1);

use App\Enums\OrderStatus;
use App\Models\Order;
use Inertia\Testing\AssertableInertia as Assert;

it('lists only the customers own orders', function (): void {
    $user = createCustomer();
    Order::factory()->for($user)->count(2)->create();
    Order::factory()->create();

    $this->actingAs($user)->get(route('account.orders.index'))
        ->assertInertia(fn (Assert $page) => $page->component('account/orders/index')->has('orders.data', 2));
});

it('shows an order to its owner but not to other customers', function (): void {
    $order = Order::factory()->create();

    $this->actingAs($order->user)->get(route('account.orders.show', $order))->assertOk();
    $this->actingAs(createCustomer())->get(route('account.orders.show', $order))->assertForbidden();
});

it('redirects guests to the login page', function (): void {
    $this->get(route('account.orders.show', Order::factory()->create()))->assertRedirect(route('login'));
});

it('lets administrators open any order through the admin area only', function (): void {
    $order = Order::factory()->create();

    $this->actingAs(createAdmin())->get(route('admin.orders.show', $order))->assertOk();
});

it('cancels a pending unpaid order and restocks it', function (): void {
    $user    = createCustomer();
    $product = createProduct(['stock_quantity' => 10]);
    cartWith($user, [$product->id => 3]);
    $this->actingAs($user)->post(route('checkout.store'), checkoutPayload());
    $order = Order::query()->firstOrFail();
    expect($product->fresh()->stock_quantity)->toBe(7);

    $this->actingAs($user)->post(route('account.orders.cancel', $order), ['reason' => 'Changed my mind'])->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->fresh()->cancellation_reason)->toBe('Changed my mind')
        ->and($product->fresh()->stock_quantity)->toBe(10);
});

it('refuses to cancel orders that are already being fulfilled', function (): void {
    $order = Order::factory()->status(OrderStatus::Shipped)->create();

    $this->actingAs($order->user)->post(route('account.orders.cancel', $order))->assertForbidden();

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

it('prevents cancelling someone elses order', function (): void {
    $order = Order::factory()->create();

    $this->actingAs(createCustomer())->post(route('account.orders.cancel', $order))->assertForbidden();
});

it('downloads a PDF invoice for the owner only', function (): void {
    $order = Order::factory()->create();
    $order->items()->create([
        'product_name' => 'Thing', 'product_sku' => 'T-1', 'unit_price' => 1000, 'quantity' => 1, 'line_total' => 1000,
    ]);

    $response = $this->actingAs($order->user)->get(route('account.orders.invoice', $order));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');

    $this->actingAs(createCustomer())->get(route('account.orders.invoice', $order))->assertForbidden();
});
