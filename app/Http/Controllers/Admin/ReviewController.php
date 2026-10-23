<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\ReviewRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewApprovalRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function index(Request $request, ReviewRepositoryInterface $repository): Response
    {
        $search = $this->searchTerm($request);
        $status = $request->string('status')->toString();

        $approved = match ($status) {
            'approved' => true,
            'pending'  => false,
            default    => null,
        };

        return Inertia::render('admin/reviews', [
            'reviews' => ReviewResource::collection($repository->paginateForAdmin($search, $approved)),
            'filters' => ['search' => $search, 'status' => $approved === null ? null : $status],
        ]);
    }

    public function update(ReviewApprovalRequest $request, Review $review): RedirectResponse
    {
        $approved = $request->boolean('is_approved');

        $this->reviews->setApproval($review, $approved);

        $this->toast($approved ? __('Review approved.') : __('Review hidden from the storefront.'));

        return back();
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->reviews->delete($review);

        $this->toast(__('Review deleted.'));

        return back();
    }
}
