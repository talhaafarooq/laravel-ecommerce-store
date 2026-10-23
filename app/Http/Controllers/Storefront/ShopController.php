<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ProductIndexRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductCardResource;
use App\Models\Category;
use App\Services\CatalogService;
use Inertia\Inertia;
use Inertia\Response;

final class ShopController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function index(ProductIndexRequest $request): Response
    {
        return $this->render($request);
    }

    public function category(ProductIndexRequest $request, string $slug): Response
    {
        return $this->render($request, $this->catalog->findCategory($slug));
    }

    private function render(ProductIndexRequest $request, ?Category $category = null): Response
    {
        $filters = $request->filters();
        $options = $this->catalog->filterOptions($category);

        return Inertia::render('storefront/shop', [
            'products'       => ProductCardResource::collection($this->catalog->browse($filters, $category)),
            'filters'        => $filters->toArray(),
            'category'       => $category === null ? null : CategoryResource::make($category->loadMissing('parent'))->resolve(),
            'brands'         => BrandResource::collection($options['brands'])->resolve(),
            'priceRange'     => $options['price'],
            'categoryCounts' => $this->catalog->categoryCounts(),
            'sortOptions'    => ProductSort::options(),
        ]);
    }
}
