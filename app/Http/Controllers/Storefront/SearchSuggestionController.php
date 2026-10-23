<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\SearchSuggestionRequest;
use App\Http\Resources\ProductCardResource;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;

final class SearchSuggestionController extends Controller
{
    public function __invoke(SearchSuggestionRequest $request, CatalogService $catalog): JsonResponse
    {
        return response()->json([
            'products' => ProductCardResource::collection($catalog->suggestions($request->term()))->resolve(),
        ]);
    }
}
