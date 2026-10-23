<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreReviewRequest;
use App\Models\Review;
use App\Services\CatalogService;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

final class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function store(StoreReviewRequest $request, CatalogService $catalog, string $slug): RedirectResponse
    {
        $review = $this->reviews->submit($request->user(), $catalog->findProduct($slug), $request->reviewData());

        $this->toast($review->is_approved
                ? __('Thanks! Your review has been published.')
                : __('Thanks! Your review will appear once it has been approved.'));

        return back();
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $this->reviews->delete($review);

        $this->toast(__('Your review was deleted.'));

        return back();
    }
}
