<?php

declare(strict_types = 1);

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Exceptions\UserManagementException;
use App\Models\Order;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Services\UserService;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => $this->admin = createAdmin());

describe('orders', function (): void {
    it('walks an order through its lifecycle and notifies the customer', function (): void {
        Notification::fake();
        $order = Order::factory()->create();

        foreach (['processing', 'shipped', 'delivered'] as $status) {
            $this->actingAs($this->admin)->patch(route('admin.orders.status', $order), ['status' => $status, 'note' => 'ok'])->assertRedirect();
        }

        $order->refresh();
        expect($order->status)->toBe(OrderStatus::Delivered)
            ->and($order->shipped_at)->not->toBeNull()
            ->and($order->delivered_at)->not->toBeNull()
            ->and($order->statusHistories()->count())->toBe(3);
        Notification::assertSentTo($order->user, OrderStatusUpdatedNotification::class, 3);
    });

    it('settles cash on delivery orders when they are delivered', function (): void {
        $order = Order::factory()->status(OrderStatus::Shipped)->create(['payment_method' => PaymentMethod::CashOnDelivery, 'payment_status' => PaymentStatus::Unpaid]);

        $this->actingAs($this->admin)->patch(route('admin.orders.status', $order), ['status' => 'delivered']);

        expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
    });

    it('rejects impossible transitions', function (): void {
        $order = Order::factory()->create();

        $this->actingAs($this->admin)->patchJson(route('admin.orders.status', $order), ['status' => 'delivered'])
            ->assertUnprocessable()->assertJsonValidationErrorFor('status');
        $this->patchJson(route('admin.orders.status', $order), ['status' => 'nonsense'])->assertJsonValidationErrors('status');

        expect($order->fresh()->status)->toBe(OrderStatus::Pending);
    });

    it('restocks when an administrator cancels', function (): void {
        $product = createProduct(['stock_quantity' => 7]);
        $order   = Order::factory()->create();
        $order->items()->create(['product_id' => $product->id, 'product_name' => 'P', 'product_sku' => 'P', 'unit_price' => 100, 'quantity' => 3, 'line_total' => 300]);

        $this->actingAs($this->admin)->patch(route('admin.orders.status', $order), ['status' => 'cancelled']);

        expect($product->fresh()->stock_quantity)->toBe(10);
    });

    it('filters by status and searches by order number', function (): void {
        Order::factory()->create(['number' => 'ORD-AAA111']);
        Order::factory()->status(OrderStatus::Shipped)->create();

        $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'shipped']))->assertInertia(fn ($p) => $p->has('orders.data', 1));
        $this->get(route('admin.orders.index', ['search' => 'AAA111']))->assertInertia(fn ($p) => $p->has('orders.data', 1));
    });

    it('streams an invoice', function (): void {
        $this->actingAs($this->admin)->get(route('admin.orders.invoice', Order::factory()->create()))->assertOk()->assertHeader('content-type', 'application/pdf');
    });
});

describe('users', function (): void {
    it('changes roles and deactivates accounts', function (): void {
        $user = createCustomer();

        $this->actingAs($this->admin)->patch(route('admin.users.access', $user), ['role' => 'admin', 'is_active' => '0'])->assertRedirect();

        expect($user->fresh()->role)->toBe(UserRole::Admin)->and($user->fresh()->is_active)->toBeFalse();
    });

    it('stops administrators removing their own access', function (): void {
        $this->actingAs($this->admin)->patchJson(route('admin.users.access', $this->admin), ['role' => 'customer', 'is_active' => '1'])->assertUnprocessable();
        $this->patchJson(route('admin.users.access', $this->admin), ['role' => 'admin', 'is_active' => '0'])->assertUnprocessable();

        expect($this->admin->fresh()->isAdmin())->toBeTrue();
    });

    it('never lets the last active administrator be demoted', function (): void {
        $inactiveActor = createAdmin(['is_active' => false]);

        expect(fn () => app(UserService::class)->updateAccess($this->admin, UserRole::Customer, true, $inactiveActor))
            ->toThrow(UserManagementException::class);
        expect($this->admin->fresh()->isAdmin())->toBeTrue();
    });

    it('lets one administrator demote another', function (): void {
        $other = createAdmin();

        $this->actingAs($this->admin)->patch(route('admin.users.access', $other), ['role' => 'customer', 'is_active' => '1'])->assertRedirect();

        expect($other->fresh()->role)->toBe(UserRole::Customer);
    });

    it('lists and searches customers', function (): void {
        createCustomer(['name' => 'Zed Findable']);

        $this->actingAs($this->admin)->get(route('admin.users.index', ['search' => 'Findable']))->assertInertia(fn ($p) => $p->has('users.data', 1));
        $this->get(route('admin.users.show', createCustomer()))->assertOk();
    });
});
