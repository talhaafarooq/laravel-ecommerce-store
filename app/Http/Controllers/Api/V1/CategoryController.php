<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\CatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CategoryController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    /**
     * The active category tree.
     *
     * @unauthenticated
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->catalog->navigation());
    }

    /**
     * @unauthenticated
     */
    public function show(string $slug): CategoryResource
    {
        return CategoryResource::make($this->catalog->findCategory($slug)->load('children'));
    }
}
