<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Imports the demo reviews bundled with the catalogue, creating a customer
 * account for each reviewer.
 */
final class ReviewSeeder extends Seeder
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function run(): void
    {
        /** @var array{products: list<array{slug: string, reviews: list<array{name: string, email: string, rating: int, comment: string}>}>} $catalog */
        $catalog  = File::json(database_path('data/catalog.json'));
        $products = Product::query()->pluck('id', 'slug');
        $users    = [];

        foreach ($catalog['products'] as $item) {
            $productId = $products[$item['slug']] ?? null;

            if ($productId === null) {
                continue;
            }

            foreach ($item['reviews'] as $review) {
                $users[$review['email']] ??= User::query()->where('email', $review['email'])->value('id')
                    ?? User::factory()->create(['name' => $review['name'], 'email' => $review['email']])->id;

                Review::query()->updateOrCreate(
                    ['product_id' => $productId, 'user_id' => $users[$review['email']]],
                    [
                        'rating'               => max(1, min(5, $review['rating'])),
                        'title'                => null,
                        'body'                 => $review['comment'],
                        'is_approved'          => true,
                        'is_verified_purchase' => (bool) random_int(0, 1),
                        'created_at'           => now()->subDays(random_int(1, 90)),
                    ],
                );
            }
        }

        Product::query()->select(['id'])->each(fn (Product $product) => $this->reviews->refreshProductRating($product));
    }
}
