<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\ReviewRepositoryInterface;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class ReviewService
{
    public function __construct(private ReviewRepositoryInterface $reviews) {}

    /**
     * Create or update the customer's review of a product.
     *
     * @param array{rating: int, title?: string|null, body: string} $data
     */
    public function submit(User $user, Product $product, array $data): Review
    {
        return DB::transaction(function () use ($user, $product, $data): Review {
            $review = $this->reviews->findForUser($user, $product) ?? new Review([
                'user_id'     => $user->id,
                'is_approved' => (bool) config('shop.reviews.auto_approve', true),
            ]);

            $review->fill([
                'rating'               => $data['rating'],
                'title'                => $data['title'] ?? null,
                'body'                 => $data['body'],
                'is_verified_purchase' => $this->reviews->hasPurchased($user, $product),
            ]);

            $product->reviews()->save($review);

            $this->refreshProductRating($product);

            return $review;
        });
    }

    public function setApproval(Review $review, bool $approved): void
    {
        $review->loadMissing('product');

        DB::transaction(function () use ($review, $approved): void {
            $review->forceFill(['is_approved' => $approved])->save();

            $this->refreshProductRating($review->product);
        });
    }

    public function delete(Review $review): void
    {
        $review->loadMissing('product');

        DB::transaction(function () use ($review): void {
            $product = $review->product;

            $review->delete();

            $this->refreshProductRating($product);
        });
    }

    /**
     * Recalculate the denormalised rating figures shown on product cards.
     */
    public function refreshProductRating(Product $product): void
    {
        $aggregate = $this->reviews->approvedAggregate($product);

        $product->forceFill([
            'rating_average' => $aggregate['average'],
            'reviews_count'  => $aggregate['count'],
        ])->saveQuietly();
    }
}
