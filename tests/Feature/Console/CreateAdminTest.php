<?php

declare(strict_types = 1);

use App\Enums\UserRole;
use App\Models\User;

it('creates an administrator with a strong password', function (): void {
    $this->artisan('shop:create-admin', ['email' => 'boss@example.com', '--name' => 'Boss'])
        ->expectsQuestion('Password for the new administrator', 'Str0ng-Passw0rd!x')
        ->assertSuccessful();

    $user = User::query()->where('email', 'boss@example.com')->firstOrFail();
    expect($user->role)->toBe(UserRole::Admin)->and($user->hasVerifiedEmail())->toBeTrue()->and($user->is_active)->toBeTrue();
});

it('refuses weak passwords', function (): void {
    $this->artisan('shop:create-admin', ['email' => 'weak@example.com'])
        ->expectsQuestion('Password for the new administrator', 'password')
        ->assertFailed();

    expect(User::query()->where('email', 'weak@example.com')->exists())->toBeFalse();
});

it('promotes an existing account', function (): void {
    $user = createCustomer(['is_active' => false]);

    $this->artisan('shop:create-admin', ['email' => $user->email])->assertSuccessful();

    expect($user->fresh()->isAdmin())->toBeTrue()->and($user->fresh()->is_active)->toBeTrue();
});
