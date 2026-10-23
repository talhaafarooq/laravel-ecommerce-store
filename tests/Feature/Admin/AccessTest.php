<?php

declare(strict_types = 1);
use App\Models\Category;

it('keeps every admin page away from guests and customers', function (string $route): void {
    $this->get(route($route))->assertRedirect(route('login'));
    $this->actingAs(createCustomer())->get(route($route))->assertForbidden();
})->with([
    'dashboard' => 'admin.dashboard', 'products' => 'admin.products.index', 'orders' => 'admin.orders.index',
    'users'     => 'admin.users.index', 'inventory' => 'admin.inventory.index', 'reports' => 'admin.reports.index',
    'coupons'   => 'admin.coupons.index', 'reviews' => 'admin.reviews.index', 'messages' => 'admin.messages.index',
]);

it('blocks write actions for customers', function (): void {
    $this->actingAs(createCustomer());

    $this->post(route('admin.products.store'), [])->assertForbidden();
    $this->post(route('admin.coupons.store'), [])->assertForbidden();
    $this->delete(route('admin.categories.destroy', Category::factory()->create()))->assertForbidden();
});

it('lets administrators in and renders the dashboard with stats', function (): void {
    $this->actingAs(createAdmin())->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/dashboard')->has('stats.revenue.formatted')->has('salesChart', 30));
});

it('turns deactivated administrators away', function (): void {
    $this->actingAs(createAdmin(['is_active' => false]))->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('redirects the generic dashboard by role', function (): void {
    $this->actingAs(createAdmin())->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
});

it('blocks inactive customers from logging in', function (): void {
    $user = createCustomer(['is_active' => false, 'password' => 'password']);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors();
    $this->assertGuest();
});
