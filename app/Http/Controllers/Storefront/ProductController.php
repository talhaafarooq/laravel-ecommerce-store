<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Contracts\Repositories\ReviewRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Models\User;
use App\Services\CatalogService;
use App\Services\RecentlyViewedService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly ReviewRepositoryInterface $reviews,
        private readonly RecentlyViewedService $recentlyViewed,
    ) {}

    public function show(Request $request, string $slug): Response
    {
        $product = $this->catalog->findProduct($slug);
        $user    = $request->user();
        $session = $request->session();

        $recent = $this->recentlyViewed->get($session, except: $product);
        $this->recentlyViewed->record($session, $product);

        $ownReview = $user instanceof User ? $this->reviews->findForUser($user, $product) : null;

        return Inertia::render('storefront/product', [
            'product'         => ProductResource::make($product)->resolve(),
            'reviews'         => ReviewResource::collection($this->reviews->paginateApprovedForProduct($product)),
            'ratingBreakdown' => $this->reviews->ratingBreakdown($product),
            'ownReview'       => $ownReview === null ? null : ReviewResource::make($ownReview)->resolve(),
            'canReview'       => $user instanceof User,
            'related'         => Inertia::defer(fn (): array => ProductCardResource::collection($this->catalog->related($product))->resolve()),
            'recentlyViewed'  => ProductCardResource::collection($recent)->resolve(),
        ]);
    }
}
