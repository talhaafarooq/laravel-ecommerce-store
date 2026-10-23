<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Product;
use App\Services\CartSession;
use App\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlist) {}

    public function index(Request $request): Response
    {
        return Inertia::render('account/wishlist', [
            'products' => ProductCardResource::collection($this->wishlist->paginate($request->user())),
        ]);
    }

    public function toggle(Request $request, Product $product): RedirectResponse
    {
        $added = $this->wishlist->toggle($request->user(), $product);

        $this->toast($added ? __('Saved to your wishlist.') : __('Removed from your wishlist.'));

        return back();
    }

    public function moveToCart(Request $request, Product $product, CartSession $cartSession): RedirectResponse
    {
        $this->wishlist->moveToCart($request->user(), $product, $cartSession->currentOrCreate($request));

        $this->toast(__(':name moved to your cart.', ['name' => $product->name]));

        return back();
    }
}
