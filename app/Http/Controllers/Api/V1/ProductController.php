<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Repositories\ReviewRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ProductIndexRequest;
use App\Http\Requests\Storefront\SearchSuggestionRequest;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Services\CatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    /**
     * Browse the catalogue with optional filters.
     *
     * @unauthenticated
     */
    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $category = $request->filled('category') ? $this->catalog->findCategory($request->string('category')->toString()) : null;

        return ProductCardResource::collection($this->catalog->browse($request->filters(), $category));
    }

    /**
     * Quick search suggestions.
     *
     * @unauthenticated
     */
    public function search(SearchSuggestionRequest $request): AnonymousResourceCollection
    {
        return ProductCardResource::collection($this->catalog->suggestions($request->term()));
    }

    /**
     * @unauthenticated
     */
    public function show(string $slug): ProductResource
    {
        return ProductResource::make($this->catalog->findProduct($slug));
    }

    /**
     * @unauthenticated
     */
    public function related(string $slug): AnonymousResourceCollection
    {
        return ProductCardResource::collection($this->catalog->related($this->catalog->findProduct($slug)));
    }

    /**
     * Approved reviews of a product.
     *
     * @unauthenticated
     */
    public function reviews(string $slug, ReviewRepositoryInterface $reviews): AnonymousResourceCollection
    {
        return ReviewResource::collection($reviews->paginateApprovedForProduct($this->catalog->findProduct($slug)));
    }
}
