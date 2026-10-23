<?php

declare(strict_types = 1);

use App\Enums\CouponType;
use App\Models\Cart;
use App\Models\Coupon;
use Inertia\Testing\AssertableInertia as Assert;

it('lets a guest add to a cart that persists in the session', function (): void {
    $product = createProduct(['price' => 2500, 'stock_quantity' => 10]);

    $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 2])->assertRedirect();

    $this->get(route('cart.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/cart')
            ->where('cart.item_count', 2)
            ->where('cart.totals.subtotal.amount', 5000));

    expect(Cart::query()->whereNull('user_id')->count())->toBe(1);
});

it('merges quantities when the same product is added twice', function (): void {
    $product = createProduct(['stock_quantity' => 10]);

    $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 2]);

    $this->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page->has('cart.items', 1)->where('cart.items.0.quantity', 3));
});

it('refuses more units than are in stock', function (): void {
    $product = createProduct(['stock_quantity' => 2]);

    $this->postJson(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 3])
        ->assertStatus(409)
        ->assertJsonValidationErrorFor('quantity');

    expect(Cart::query()->count())->toBe(1);
});

it('refuses inactive and out of stock products', function (): void {
    $inactive = createProduct(['is_active' => false]);
    $soldOut  = createProduct(['stock_quantity' => 0]);

    $this->postJson(route('cart.items.store'), ['product_id' => $inactive->id])->assertUnprocessable();
    $this->postJson(route('cart.items.store'), ['product_id' => $soldOut->id])->assertUnprocessable();
});

it('validates the add-to-cart payload', function (): void {
    $this->postJson(route('cart.items.store'), [])->assertJsonValidationErrors('product_id');
    $this->postJson(route('cart.items.store'), ['product_id' => 1, 'quantity' => 0])->assertJsonValidationErrors('quantity');
    $this->postJson(route('cart.items.store'), ['product_id' => 1, 'quantity' => 999])->assertJsonValidationErrors('quantity');
});

it('updates and removes lines', function (): void {
    $user    = createCustomer();
    $product = createProduct(['stock_quantity' => 10]);
    $cart    = cartWith($user, [$product->id => 1]);
    $item    = $cart->items()->first();

    $this->actingAs($user)->patch(route('cart.items.update', $item->id), ['quantity' => 4])->assertRedirect();
    expect($item->fresh()->quantity)->toBe(4);

    $this->actingAs($user)->delete(route('cart.items.destroy', $item->id))->assertRedirect();
    expect($cart->items()->count())->toBe(0);
});

it('cannot touch another customers cart line', function (): void {
    $product = createProduct();
    $other   = cartWith(createCustomer(), [$product->id => 1])->items()->first();
    $mine    = createCustomer();
    cartWith($mine, [createProduct()->id => 1]);

    $this->actingAs($mine)->delete(route('cart.items.destroy', $other->id))->assertNotFound();

    expect($other->fresh())->not->toBeNull();
});

it('merges the guest cart into the account on login', function (): void {
    $user    = createCustomer(['password' => 'password']);
    $product = createProduct(['stock_quantity' => 10]);

    $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 2]);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();

    expect($user->cart->items()->first()->quantity)->toBe(2)
        ->and(Cart::query()->whereNull('user_id')->count())->toBe(0);
});

describe('coupons', function (): void {
    it('applies a percentage coupon to the totals', function (): void {
        $user    = createCustomer();
        $product = createProduct(['price' => 10000]);
        cartWith($user, [$product->id => 1]);
        Coupon::factory()->create(['code' => 'TEN', 'type' => CouponType::Percentage, 'value' => 10]);

        $this->actingAs($user)->post(route('cart.coupon.store'), ['code' => 'ten'])->assertRedirect();

        $this->actingAs($user)->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page
            ->where('cart.coupon.code', 'TEN')
            ->where('cart.totals.discount.amount', 1000)
            ->where('cart.totals.total.amount', 9000));
    });

    it('rejects unknown, expired and below-minimum coupons', function (): void {
        $user    = createCustomer();
        $product = createProduct(['price' => 1000]);
        cartWith($user, [$product->id => 1]);
        Coupon::factory()->expired()->create(['code' => 'OLD']);
        Coupon::factory()->create(['code' => 'BIG', 'min_subtotal' => 50000]);

        foreach (['NOPE', 'OLD', 'BIG'] as $code) {
            $this->actingAs($user)->postJson(route('cart.coupon.store'), ['code' => $code])
                ->assertUnprocessable()->assertJsonValidationErrorFor('code');
        }
    });

    it('requires an account for per-customer coupons', function (): void {
        $product = createProduct();
        Coupon::factory()->create(['code' => 'WELCOME', 'usage_limit_per_user' => 1]);
        $this->post(route('cart.items.store'), ['product_id' => $product->id]);

        $this->postJson(route('cart.coupon.store'), ['code' => 'WELCOME'])->assertUnprocessable();
    });
});
