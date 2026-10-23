<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlist) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ProductCardResource::collection($this->wishlist->paginate($request->user()));
    }

    public function store(Request $request, Product $product): Response
    {
        $this->wishlist->add($request->user(), $product);

        return response()->noContent(201);
    }

    public function destroy(Request $request, Product $product): Response
    {
        $this->wishlist->remove($request->user(), $product);

        return response()->noContent();
    }
}
