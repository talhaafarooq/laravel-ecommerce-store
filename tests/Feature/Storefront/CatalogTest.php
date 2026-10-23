<?php

declare(strict_types = 1);

use App\Models\Brand;
use App\Models\Category;
use App\Models\Review;
use App\Services\CatalogService;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the home page with featured products', function (): void {
    createProduct(['name' => 'Featured Gadget', 'is_featured' => true]);
    createProduct(['name' => 'Hidden Gadget', 'is_featured' => true, 'is_active' => false]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/home')
            ->has('featured', 1)
            ->where('featured.0.name', 'Featured Gadget'));
});

it('only lists active products in active categories', function (): void {
    $visible = createProduct(['name' => 'Visible']);
    createProduct(['name' => 'Inactive product', 'is_active' => false]);
    createProduct(['name' => 'Hidden category', 'category_id' => Category::factory()->inactive()->create()->id]);

    $this->get(route('shop.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/shop')
            ->has('products.data', 1)
            ->where('products.data.0.id', $visible->id));
});

it('filters by price, stock, sale and rating', function (): void {
    createProduct(['name' => 'Cheap', 'price' => 1000]);
    createProduct(['name' => 'Pricey', 'price' => 90000]);
    createProduct(['name' => 'Sold out', 'price' => 2000, 'stock_quantity' => 0]);
    createProduct(['name' => 'Discounted', 'price' => 3000, 'compare_at_price' => 5000]);
    createProduct(['name' => 'Loved', 'price' => 4000, 'rating_average' => 4.6, 'reviews_count' => 10]);

    $names = fn (array $query) => collect($this->get(route('shop.index', $query))->inertiaProps('products.data'))->pluck('name')->sort()->values()->all();

    expect($names(['min_price' => 10, 'max_price' => 35]))->toBe(['Cheap', 'Discounted', 'Sold out'])
        ->and($names(['in_stock' => 1, 'max_price' => 25]))->toBe(['Cheap'])
        ->and($names(['on_sale' => 1]))->toBe(['Discounted'])
        ->and($names(['min_rating' => 4]))->toBe(['Loved']);
});

it('filters by brand and searches by name or sku', function (): void {
    $acme = Brand::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    createProduct(['name' => 'Rocket Skates', 'brand_id' => $acme->id, 'sku' => 'RS-1001']);
    createProduct(['name' => 'Plain Anvil']);

    $names = fn (array $query) => collect($this->get(route('shop.index', $query))->inertiaProps('products.data'))->pluck('name')->all();

    expect($names(['brands' => ['acme']]))->toBe(['Rocket Skates'])
        ->and($names(['q' => 'anvil']))->toBe(['Plain Anvil'])
        ->and($names(['q' => 'RS-1001']))->toBe(['Rocket Skates'])
        ->and($names(['q' => 'nothing-matches-this']))->toBe([]);
});

it('sorts by price in both directions', function (): void {
    createProduct(['name' => 'B', 'price' => 2000]);
    createProduct(['name' => 'A', 'price' => 1000]);
    createProduct(['name' => 'C', 'price' => 3000]);

    $order = fn (string $sort) => collect($this->get(route('shop.index', ['sort' => $sort]))->inertiaProps('products.data'))->pluck('name')->all();

    expect($order('price_asc'))->toBe(['A', 'B', 'C'])
        ->and($order('price_desc'))->toBe(['C', 'B', 'A']);
});

it('rejects invalid filter input', function (): void {
    $this->get(route('shop.index', ['sort' => 'bogus', 'min_rating' => 9, 'per_page' => 1000]))
        ->assertSessionHasErrors(['sort', 'min_rating', 'per_page']);
});

it('shows products of sub-categories on the parent category page', function (): void {
    $parent = Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);
    $child  = Category::factory()->childOf($parent)->create();
    $other  = createProduct();
    $inside = createProduct(['category_id' => $child->id]);

    $this->get(route('categories.show', 'electronics'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/shop')
            ->where('category.slug', 'electronics')
            ->has('products.data', 1)
            ->where('products.data.0.id', $inside->id));

    expect($other->id)->not->toBe($inside->id);
});

it('returns 404 for unknown or inactive categories', function (): void {
    $inactive = Category::factory()->inactive()->create(['slug' => 'secret']);

    $this->get(route('categories.show', 'secret'))->assertNotFound();
    $this->get(route('categories.show', 'does-not-exist'))->assertNotFound();

    expect($inactive->is_active)->toBeFalse();
});

it('shows a product with its approved reviews only', function (): void {
    $product = createProduct(['name' => 'Reviewed Thing']);
    Review::factory()->for($product)->create(['body' => 'Loved it, great build quality.', 'is_approved' => true]);
    Review::factory()->for($product)->create(['body' => 'Hidden pending review text.', 'is_approved' => false]);

    $this->get(route('products.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/product')
            ->where('product.name', 'Reviewed Thing')
            ->has('reviews.data', 1)
            ->where('reviews.data.0.body', 'Loved it, great build quality.'));
});

it('does not expose inactive products', function (): void {
    $product = createProduct(['is_active' => false]);

    $this->get(route('products.show', $product->slug))->assertNotFound();
});

it('remembers recently viewed products in the session', function (): void {
    $first  = createProduct(['name' => 'First seen']);
    $second = createProduct(['name' => 'Second seen']);

    $this->get(route('products.show', $first->slug))->assertOk();

    $this->get(route('products.show', $second->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentlyViewed', 1)
            ->where('recentlyViewed.0.name', 'First seen'));
});

describe('search suggestions', function (): void {
    it('returns lean results for visible products', function (): void {
        createProduct(['name' => 'Wireless Headphones']);
        createProduct(['name' => 'Wireless Hidden', 'is_active' => false]);

        $this->getJson(route('search.suggestions', ['q' => 'wireless']))
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', 'Wireless Headphones')
            ->assertJsonStructure(['products' => [['id', 'name', 'slug', 'price' => ['amount', 'formatted'], 'image', 'category']]]);
    });

    it('requires at least two characters', function (): void {
        $this->getJson(route('search.suggestions', ['q' => 'a']))->assertUnprocessable()->assertJsonValidationErrors('q');
    });

    it('treats wildcard characters literally', function (): void {
        createProduct(['name' => 'Ordinary Lamp']);

        $this->getJson(route('search.suggestions', ['q' => '%%']))->assertOk()->assertJsonCount(0, 'products');
        $this->getJson(route('search.suggestions', ['q' => '__']))->assertOk()->assertJsonCount(0, 'products');
    });
});

describe('catalogue cache', function (): void {
    it('survives a round trip through a serialising cache store', function (): void {
        // The cache refuses to unserialise classes, so only plain data may be stored.
        config(['cache.stores.array.serialize' => true]);
        Cache::purge('array');
        Cache::store('array')->flush();

        $root = Category::factory()->create(['name' => 'Home']);
        Category::factory()->childOf($root)->create(['name' => 'Kitchen']);
        createProduct(['category_id' => Category::query()->where('name', 'Kitchen')->value('id')]);

        $catalog = app(CatalogService::class);

        $cold = $catalog->navigation();
        $warm = $catalog->navigation();

        expect($warm)->toHaveCount(1)
            ->and($warm->first())->toBeInstanceOf(Category::class)
            ->and($warm->first()->name)->toBe('Home')
            ->and($warm->first()->children->pluck('name')->all())->toBe(['Kitchen'])
            ->and($warm->pluck('name')->all())->toBe($cold->pluck('name')->all());

        $options = $catalog->filterOptions();
        $again   = $catalog->filterOptions();

        expect($again['price']['min']->amount)->toBe($options['price']['min']->amount)
            ->and($again['price']['max'])->toBeInstanceOf(Money::class);
    });

    it('serves the home page twice from a serialising cache', function (): void {
        config(['cache.stores.array.serialize' => true]);
        Cache::purge('array');

        createProduct(['is_featured' => true]);

        $this->get(route('home'))->assertOk();
        $this->get(route('home'))->assertOk();
    });

    it('drops cached catalogue data when it is flushed', function (): void {
        $root = Category::factory()->create(['name' => 'Garden']);

        $catalog = app(CatalogService::class);
        expect($catalog->navigation())->toHaveCount(1);

        Category::factory()->create(['name' => 'Toys']);
        expect($catalog->navigation())->toHaveCount(1);

        $catalog->flush();
        expect($catalog->navigation())->toHaveCount(2);

        expect($root->exists)->toBeTrue();
    });
});
