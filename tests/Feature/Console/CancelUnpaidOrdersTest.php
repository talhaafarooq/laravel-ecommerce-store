<?php

declare(strict_types = 1);

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;

function staleOrder(array $attributes = []): Order
{
    return Order::factory()->stripe()->create(array_merge([
        'status' => OrderStatus::Pending, 'payment_status' => PaymentStatus::Unpaid, 'placed_at' => now()->subHours(3),
    ], $attributes));
}

it('cancels online orders that were never paid and restocks them', function (): void {
    $product = createProduct(['stock_quantity' => 5]);
    $order   = staleOrder();
    $order->items()->create(['product_id' => $product->id, 'product_name' => 'X', 'product_sku' => 'X', 'unit_price' => 100, 'quantity' => 2, 'line_total' => 200]);
    $product->update(['stock_quantity' => 3]);

    $this->artisan('orders:cancel-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)->and($product->fresh()->stock_quantity)->toBe(5);
});

it('leaves recent, paid and cash on delivery orders alone', function (): void {
    $recent = staleOrder(['placed_at' => now()->subMinutes(10)]);
    $paid   = staleOrder(['payment_status' => PaymentStatus::Paid]);
    $cod    = staleOrder(['payment_method' => PaymentMethod::CashOnDelivery]);

    $this->artisan('orders:cancel-unpaid')->assertSuccessful();

    foreach ([$recent, $paid, $cod] as $order) {
        expect($order->fresh()->status)->toBe(OrderStatus::Pending);
    }
});

it('honours a custom payment window', function (): void {
    $order = staleOrder(['placed_at' => now()->subMinutes(20)]);

    $this->artisan('orders:cancel-unpaid', ['--minutes' => 15])->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});
