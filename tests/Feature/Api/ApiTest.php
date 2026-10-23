<?php

declare(strict_types = 1);

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

function apiLogin(?User $user = null): array
{
    $user ??= createCustomer(['password' => 'password']);
    $token = test()->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])->assertOk()->json('access_token');

    return [$user, ['Authorization' => "Bearer {$token}"]];
}

describe('authentication', function (): void {
    it('registers a customer and returns a bearer token', function (): void {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'Str0ng-Passw0rd!x', 'password_confirmation' => 'Str0ng-Passw0rd!x',
        ])->assertCreated()->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user' => ['id', 'email', 'role']])
            ->assertJsonMissingPath('user.password');

        expect(User::query()->where('email', 'ada@example.com')->exists())->toBeTrue();
    });

    it('validates registration', function (): void {
        createCustomer(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/auth/register', ['name' => 'x', 'email' => 'taken@example.com', 'password' => 'short', 'password_confirmation' => 'nope'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    });

    it('logs in, identifies the user and logs out', function (): void {
        [$user, $headers] = apiLogin();

        $this->getJson('/api/v1/auth/me', $headers)->assertOk()->assertJsonPath('data.id', $user->id);
        $this->postJson('/api/v1/auth/refresh', [], $headers)->assertOk()->assertJsonStructure(['access_token']);
        $this->postJson('/api/v1/auth/logout', [], $headers)->assertNoContent();
    });

    it('rejects wrong credentials and deactivated accounts without leaking which', function (): void {
        $user = createCustomer(['password' => 'password']);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/v1/auth/login', ['email' => 'ghost@example.com', 'password' => 'password'])->assertUnprocessable()->assertJsonValidationErrors('email');

        $user->forceFill(['is_active' => false])->save();
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])->assertUnprocessable();
    });

    it('requires a valid token on protected routes', function (): void {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/cart', ['Authorization' => 'Bearer not-a-token'])->assertUnauthorized();
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    });

    it('rate limits login attempts', function (): void {
        RateLimiter::clear('api-auth');

        foreach (range(1, 10) as $_) {
            $this->postJson('/api/v1/auth/login', ['email' => 'a@b.test', 'password' => 'x']);
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'a@b.test', 'password' => 'x'])->assertTooManyRequests();
    });
});

describe('catalogue', function (): void {
    it('lists, filters and shows products without authentication', function (): void {
        $product = createProduct(['name' => 'API Widget', 'price' => 1999]);
        createProduct(['is_active' => false]);

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.price.amount', 1999)->assertJsonStructure(['links', 'meta']);
        $this->getJson('/api/v1/products?q=widget')->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/products/{$product->slug}")->assertOk()->assertJsonPath('data.name', 'API Widget');
        $this->getJson('/api/v1/products/nope')->assertNotFound();
        $this->getJson('/api/v1/products/search?q=widg')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/products/{$product->slug}/related")->assertOk();
        $this->getJson('/api/v1/categories')->assertOk();
    });

    it('does not expose internal columns', function (): void {
        $product = createProduct();

        $this->getJson("/api/v1/products/{$product->slug}")->assertJsonMissingPath('data.deleted_at')->assertJsonMissingPath('data.cost');
    });
});

describe('cart and orders', function (): void {
    it('runs the full buying flow over the API', function (): void {
        [$user, $headers] = apiLogin();
        $product          = createProduct(['price' => 6000, 'stock_quantity' => 4]);

        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2], $headers)->assertCreated()->assertJsonPath('data.item_count', 2);
        $this->getJson('/api/v1/cart', $headers)->assertOk()->assertJsonPath('data.totals.subtotal.amount', 12000);

        $response = $this->postJson('/api/v1/orders', checkoutPayload(), $headers)->assertCreated()->assertJsonPath('data.status.value', 'pending');
        $number   = $response->json('data.number');

        expect($product->fresh()->stock_quantity)->toBe(2);
        $this->getJson('/api/v1/cart', $headers)->assertJsonPath('data.is_empty', true);
        $this->getJson('/api/v1/orders', $headers)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/orders/{$number}", $headers)->assertOk()->assertJsonPath('data.grand_total.amount', 12000);
        $this->postJson("/api/v1/orders/{$number}/cancel", [], $headers)->assertOk()->assertJsonPath('data.status.value', 'cancelled');
        expect($product->fresh()->stock_quantity)->toBe(4);
    });

    it('reports stock problems as a 409 with an error body', function (): void {
        [, $headers] = apiLogin();
        $product     = createProduct(['stock_quantity' => 1]);

        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 5], $headers)
            ->assertStatus(409)->assertJsonStructure(['message', 'errors' => ['quantity']]);
    });

    it('cannot read another customers order', function (): void {
        [, $headers] = apiLogin();
        $order       = Order::factory()->create();

        $this->getJson("/api/v1/orders/{$order->number}", $headers)->assertForbidden();
        $this->postJson("/api/v1/orders/{$order->number}/cancel", [], $headers)->assertForbidden();
    });

    it('manages the wishlist', function (): void {
        [, $headers] = apiLogin();
        $product     = createProduct();

        $this->postJson("/api/v1/wishlist/{$product->slug}", [], $headers)->assertCreated();
        $this->getJson('/api/v1/wishlist', $headers)->assertJsonCount(1, 'data');
        $this->deleteJson("/api/v1/wishlist/{$product->slug}", [], $headers)->assertNoContent();
        $this->getJson('/api/v1/wishlist', $headers)->assertJsonCount(0, 'data');
    });

    it('creates one review per product and lets the author delete it', function (): void {
        [, $headers] = apiLogin();
        $product     = createProduct();

        $this->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5, 'body' => 'Absolutely excellent product.'], $headers)->assertCreated();
        $id = $this->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 4, 'body' => 'Updated my thoughts on it.'], $headers)->assertOk()->json('data.id');
        $this->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 9, 'body' => 'x'], $headers)->assertJsonValidationErrors(['rating', 'body']);

        expect($product->reviews()->count())->toBe(1);
        $this->deleteJson("/api/v1/reviews/{$id}", [], $headers)->assertNoContent();
    });

    it('updates the profile', function (): void {
        [$user, $headers] = apiLogin();

        $this->postJson('/api/v1/profile', ['name' => 'New Name', 'email' => $user->email, 'phone' => '+1 555 1234'], $headers)->assertOk()->assertJsonPath('data.name', 'New Name');
    });
});
