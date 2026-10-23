<?php

declare(strict_types = 1);

use App\Models\User;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('customers are sent to their account dashboard', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertRedirect(route('account.dashboard'));
});

test('administrators are sent to the admin dashboard', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
});

test('deactivated administrators lose admin access', function (): void {
    $this->actingAs(User::factory()->admin()->inactive()->create());

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
