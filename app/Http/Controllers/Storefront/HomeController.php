<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Contracts\Repositories\PostRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\ProductCardResource;
use App\Services\CatalogService;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(CatalogService $catalog, PostRepositoryInterface $posts): Response
    {
        $sections = $catalog->homepage();

        return Inertia::render('storefront/home', [
            'featured'    => ProductCardResource::collection($sections['featured'])->resolve(),
            'newArrivals' => ProductCardResource::collection($sections['new_arrivals'])->resolve(),
            'bestSellers' => ProductCardResource::collection($sections['best_sellers'])->resolve(),
            'deals'       => ProductCardResource::collection($sections['deals'])->resolve(),
            'categories'  => CategoryResource::collection($sections['categories'])->resolve(),
            'posts'       => Inertia::defer(fn (): array => PostResource::collection($posts->latestPublished(3))->resolve()),
        ]);
    }
}
