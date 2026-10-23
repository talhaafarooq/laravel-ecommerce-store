<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\CatalogService;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    /**
     * Create or replace your review of a product.
     */
    public function store(StoreReviewRequest $request, CatalogService $catalog, string $slug): JsonResponse
    {
        $review = $this->reviews->submit($request->user(), $catalog->findProduct($slug), $request->reviewData());

        return ReviewResource::make($review->load('user'))
            ->response()
            ->setStatusCode($review->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Review $review): Response
    {
        $this->authorize('delete', $review);

        $this->reviews->delete($review);

        return response()->noContent();
    }
}
