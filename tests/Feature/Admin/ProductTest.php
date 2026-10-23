<?php

declare(strict_types = 1);

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function productForm(array $overrides = []): array
{
    return array_merge([
        'name'  => 'Desk Lamp', 'sku' => 'lamp-001', 'category_id' => Category::factory()->create()->id,
        'price' => '49.90', 'stock_quantity' => 12, 'low_stock_threshold' => 3, 'is_active' => '1', 'is_featured' => '0',
    ], $overrides);
}

beforeEach(function (): void {
    Storage::fake('public');
    $this->admin = createAdmin();
});

it('creates a product with optimised images, opening stock and a ledger entry', function (): void {
    $response = $this->actingAs($this->admin)->post(route('admin.products.store'), productForm([
        'images' => [UploadedFile::fake()->image('front.jpg', 1800, 1200), UploadedFile::fake()->image('back.png', 600, 600)],
    ]));

    $product = Product::query()->firstOrFail();
    $response->assertRedirect(route('admin.products.edit', $product));

    expect($product->slug)->toBe('desk-lamp')
        ->and($product->sku)->toBe('LAMP-001')
        ->and($product->price->amount)->toBe(4990)
        ->and($product->stock_quantity)->toBe(12)
        ->and($product->stockMovements()->count())->toBe(1)
        ->and($product->images)->toHaveCount(2);

    foreach ($product->images as $image) {
        expect($image->path)->toEndWith('.webp');
        Storage::disk('public')->assertExists([$image->path, $image->thumbnail_path]);
    }
});

it('validates the product form', function (): void {
    Product::factory()->create(['sku' => 'TAKEN-1', 'category_id' => Category::factory()]);

    $this->actingAs($this->admin)->post(route('admin.products.store'), [
        'name'             => '', 'sku' => 'taken-1', 'price' => '-5', 'stock_quantity' => -1, 'category_id' => 9999,
        'compare_at_price' => '1.00',
    ])->assertSessionHasErrors(['name', 'sku', 'price', 'stock_quantity', 'category_id', 'low_stock_threshold']);
});

it('rejects non-image uploads and executables disguised as images', function (): void {
    $this->actingAs($this->admin)->post(route('admin.products.store'), productForm([
        'images' => [UploadedFile::fake()->create('shell.php', 10, 'application/x-php'), UploadedFile::fake()->create('evil.jpg', 10, 'text/plain')],
    ]))->assertSessionHasErrors(['images.0', 'images.1']);

    expect(Product::query()->count())->toBe(0);
});

it('requires the compare-at price to exceed the price', function (): void {
    $this->actingAs($this->admin)->post(route('admin.products.store'), productForm(['compare_at_price' => '10.00']))
        ->assertSessionHasErrors('compare_at_price');
});

it('updates a product and records stock changes in the ledger', function (): void {
    $product = createProduct(['stock_quantity' => 10, 'name' => 'Old']);

    $this->actingAs($this->admin)->put(route('admin.products.update', $product), productForm([
        'name' => 'New', 'sku' => $product->sku, 'category_id' => $product->category_id, 'stock_quantity' => 25,
    ]))->assertRedirect();

    expect($product->fresh()->name)->toBe('New')
        ->and($product->fresh()->stock_quantity)->toBe(25)
        ->and($product->stockMovements()->where('quantity', 15)->exists())->toBeTrue();
});

it('archives and restores without losing order history', function (): void {
    $product = createProduct();

    $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product))->assertRedirect(route('admin.products.index'));
    expect($product->fresh()->trashed())->toBeTrue();
    $this->get(route('products.show', $product->slug))->assertNotFound();

    $this->post(route('admin.products.restore', $product))->assertRedirect();
    expect($product->fresh()->trashed())->toBeFalse();
});

it('manages the image gallery', function (): void {
    $product = createProduct();
    $this->actingAs($this->admin)->post(route('admin.products.images.store', $product), [
        'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
    ])->assertRedirect();
    [$first, $second] = $product->images()->orderBy('position')->get()->all();

    $this->post(route('admin.products.images.primary', [$product, $second]))->assertRedirect();
    expect($product->images()->orderBy('position')->first()->id)->toBe($second->id);

    $this->delete(route('admin.products.images.destroy', [$product, $first]))->assertRedirect();
    Storage::disk('public')->assertMissing($first->path);
    expect($product->images()->count())->toBe(1);
});

it('refuses to edit an image through another products URL', function (): void {
    $product = createProduct();
    $other   = createProduct();
    $image   = $other->images()->create(['path' => 'x.webp', 'thumbnail_path' => 'y.webp', 'position' => 0]);

    $this->actingAs($this->admin)->delete(route('admin.products.images.destroy', [$product, $image]))->assertNotFound();
});

it('filters the product list', function (): void {
    createProduct(['name' => 'Alpha Lamp', 'stock_quantity' => 0]);
    createProduct(['name' => 'Beta Chair', 'stock_quantity' => 50]);

    $this->actingAs($this->admin)->get(route('admin.products.index', ['search' => 'lamp']))
        ->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.name', 'Alpha Lamp'));
    $this->get(route('admin.products.index', ['stock' => 'out_of_stock']))
        ->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.name', 'Alpha Lamp'));
});
