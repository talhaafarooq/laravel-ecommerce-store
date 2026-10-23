<?php

declare(strict_types = 1);

use App\Enums\CouponType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Models\Faq;
use App\Models\Order;
use App\Models\Post;
use App\Models\Review;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->actingAs(createAdmin());
});

describe('categories', function (): void {
    it('creates a nested category and generates a slug', function (): void {
        $root = Category::factory()->create();

        $this->post(route('admin.categories.store'), ['name' => 'Garden Tools', 'parent_id' => $root->id, 'is_active' => '1', 'position' => 2])->assertRedirect();

        expect(Category::query()->where('slug', 'garden-tools')->value('parent_id'))->toBe($root->id);
    });

    it('refuses to nest a category under its own child', function (): void {
        $root  = Category::factory()->create();
        $child = Category::factory()->childOf($root)->create();

        $this->putJson(route('admin.categories.update', $root), ['name' => $root->name, 'parent_id' => $child->id])->assertUnprocessable();
    });

    it('protects categories that still have products or children', function (): void {
        $category = Category::factory()->create();
        createProduct(['category_id' => $category->id]);
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent)->create();

        $this->deleteJson(route('admin.categories.destroy', $category))->assertUnprocessable();
        $this->deleteJson(route('admin.categories.destroy', $parent))->assertUnprocessable();
        $this->delete(route('admin.categories.destroy', Category::factory()->create()))->assertRedirect();
    });
});

describe('brands', function (): void {
    it('creates, renames and deletes with unique names', function (): void {
        $this->post(route('admin.brands.store'), ['name' => 'Acme', 'is_active' => '1'])->assertRedirect();
        $this->postJson(route('admin.brands.store'), ['name' => 'Acme'])->assertJsonValidationErrors('name');

        $brand = Brand::query()->firstOrFail();
        $this->put(route('admin.brands.update', $brand), ['name' => 'Acme Corp', 'is_active' => '1'])->assertRedirect();
        $this->delete(route('admin.brands.destroy', $brand))->assertRedirect();

        expect(Brand::query()->count())->toBe(0);
    });
});

describe('coupons', function (): void {
    it('stores percentage and fixed coupons in the right units', function (): void {
        $this->post(route('admin.coupons.store'), ['code' => 'save10', 'type' => 'percentage', 'value' => '10', 'max_discount' => '25.00', 'is_active' => '1'])->assertRedirect();
        $this->post(route('admin.coupons.store'), ['code' => 'FIVEOFF', 'type' => 'fixed', 'value' => '5.50', 'min_subtotal' => '30', 'is_active' => '1'])->assertRedirect();

        $percent = Coupon::query()->where('code', 'SAVE10')->firstOrFail();
        $fixed   = Coupon::query()->where('code', 'FIVEOFF')->firstOrFail();

        expect($percent->type)->toBe(CouponType::Percentage)->and($percent->value)->toBe(10)->and($percent->max_discount->amount)->toBe(2500)
            ->and($fixed->value)->toBe(550)->and($fixed->min_subtotal->amount)->toBe(3000);
    });

    it('validates codes, percentages and dates', function (): void {
        Coupon::factory()->create(['code' => 'DUP']);

        $this->postJson(route('admin.coupons.store'), ['code' => 'dup', 'type' => 'percentage', 'value' => '150'])->assertJsonValidationErrors(['code', 'value']);
        $this->postJson(route('admin.coupons.store'), ['code' => 'X', 'type' => 'fixed', 'value' => '5', 'starts_at' => '2030-01-02', 'expires_at' => '2030-01-01'])
            ->assertJsonValidationErrors('expires_at');
    });
});

describe('reviews', function (): void {
    it('approves, hides and deletes while keeping the product rating in sync', function (): void {
        $product = createProduct();
        $review  = Review::factory()->for($product)->create(['rating' => 4, 'is_approved' => false]);

        $this->patch(route('admin.reviews.update', $review), ['is_approved' => true])->assertRedirect();
        expect($product->fresh()->rating_average)->toBe(4.0)->and($product->fresh()->reviews_count)->toBe(1);

        $this->patch(route('admin.reviews.update', $review), ['is_approved' => false]);
        expect($product->fresh()->reviews_count)->toBe(0);

        $this->delete(route('admin.reviews.destroy', $review))->assertRedirect();
        expect(Review::query()->count())->toBe(0);
    });
});

describe('content', function (): void {
    it('publishes a post with a cover and sanitises HTML in the body', function (): void {
        $this->post(route('admin.posts.store'), [
            'title' => 'Hello World', 'body' => "## Hi\n\n<script>alert(1)</script>\n\n**bold**", 'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'cover' => UploadedFile::fake()->image('c.jpg', 1600, 900),
        ])->assertRedirect();

        $post = Post::query()->firstOrFail();
        expect($post->slug)->toBe('hello-world')->and($post->cover_image_path)->toEndWith('.webp')
            ->and($post->bodyHtml())->not->toContain('<script')->toContain('<strong>bold</strong>');

        $this->get(route('blog.show', 'hello-world'))->assertOk();
    });

    it('hides drafts and scheduled posts from the public blog', function (): void {
        Post::factory()->draft()->create(['slug' => 'draft-post']);
        Post::factory()->scheduled()->create(['slug' => 'later-post']);

        $this->get(route('blog.show', 'draft-post'))->assertNotFound();
        $this->get(route('blog.show', 'later-post'))->assertNotFound();
        $this->get(route('blog.index'))->assertInertia(fn ($p) => $p->has('posts.data', 0));
    });

    it('manages FAQs', function (): void {
        $this->post(route('admin.faqs.store'), ['topic' => 'Orders', 'question' => 'Where is it?', 'answer' => 'On its way.', 'is_published' => '1'])->assertRedirect();
        $faq = Faq::query()->firstOrFail();
        $this->put(route('admin.faqs.update', $faq), ['topic' => 'Orders', 'question' => 'Where?', 'answer' => 'Soon.', 'is_published' => '0'])->assertRedirect();

        $this->get(route('faq'))->assertInertia(fn ($p) => $p->has('topics', 0));
        $this->delete(route('admin.faqs.destroy', $faq))->assertRedirect();
    });

    it('marks messages read when opened', function (): void {
        $message = ContactMessage::factory()->create();

        $this->get(route('admin.messages.show', $message))->assertOk();

        expect($message->fresh()->isRead())->toBeTrue();
        $this->delete(route('admin.messages.destroy', $message))->assertRedirect(route('admin.messages.index'));
    });
});

describe('inventory and reports', function (): void {
    it('adjusts stock and refuses to go negative', function (): void {
        $product = createProduct(['stock_quantity' => 5]);

        $this->post(route('admin.inventory.adjust', $product), ['type' => 'restock', 'quantity' => 20, 'note' => 'Delivery'])->assertRedirect();
        expect($product->fresh()->stock_quantity)->toBe(25);

        $this->postJson(route('admin.inventory.adjust', $product), ['type' => 'adjustment', 'quantity' => -99])->assertUnprocessable()->assertJsonValidationErrorFor('quantity');
        $this->postJson(route('admin.inventory.adjust', $product), ['type' => 'sale', 'quantity' => 1])->assertJsonValidationErrors('type');
        $this->postJson(route('admin.inventory.adjust', $product), ['type' => 'adjustment', 'quantity' => 0])->assertJsonValidationErrors('quantity');
        expect($product->fresh()->stock_quantity)->toBe(25);
    });

    it('builds a report and exports it as CSV', function (): void {
        Order::factory()->create(['placed_at' => now()->subDay()]);

        $this->get(route('admin.reports.index'))->assertInertia(fn ($p) => $p->component('admin/reports')->has('report.daily', 30));

        $csv = $this->get(route('admin.reports.export', ['from' => now()->subDays(3)->toDateString(), 'to' => now()->toDateString()]));
        $csv->assertOk()->assertDownload();
        expect($csv->streamedContent())->toContain('Date,Orders,Revenue');
    });

    it('rejects invalid report ranges', function (): void {
        $this->get(route('admin.reports.index', ['from' => 'yesterday', 'to' => '2020-01-01']))->assertSessionHasErrors('from');
    });
});
