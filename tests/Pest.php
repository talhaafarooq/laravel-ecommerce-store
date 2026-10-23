<?php

declare(strict_types = 1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against the dedicated MySQL testing database and are
| wrapped in a transaction by RefreshDatabase. Unit tests extend the
| application test case so they can use factories and the container.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

function createAdmin(array $attributes = []): User
{
    return User::factory()->admin()->create($attributes);
}

function createCustomer(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

/**
 * A purchasable product inside an active sub-category.
 *
 * @param array<string, mixed> $attributes
 */
function createProduct(array $attributes = []): Product
{
    $category = $attributes['category_id'] ?? Category::factory()->childOf(Category::factory()->create())->create()->id;

    return Product::factory()->create([...$attributes, 'category_id' => $category]);
}

/**
 * Give the user a cart holding the given quantities, keyed by product id.
 *
 * @param array<int, int> $lines
 */
function cartWith(User $user, array $lines): Cart
{
    $cart = Cart::factory()->for($user)->create();

    foreach ($lines as $productId => $quantity) {
        $cart->items()->create(['product_id' => $productId, 'quantity' => $quantity]);
    }

    return $cart;
}

/**
 * A valid checkout payload; override any field per test.
 *
 * @param array<string, mixed> $overrides
 *
 * @return array<string, mixed>
 */
function checkoutPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'shipping' => [
            'full_name'   => 'Jordan Rivera',
            'phone'       => '+1 555 010 0002',
            'line1'       => '742 Evergreen Terrace',
            'line2'       => null,
            'city'        => 'Springfield',
            'state'       => 'OR',
            'postal_code' => '97403',
            'country'     => 'US',
        ],
        'payment_method' => 'cod',
        'notes'          => null,
    ], $overrides);
}
