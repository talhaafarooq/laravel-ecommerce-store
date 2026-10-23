<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddToCartRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use App\Services\CartSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CartController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CartSession $cartSession,
    ) {}

    public function index(Request $request): Response
    {
        $summary = $this->carts->summarize($this->cartSession->current($request), $request->user());

        return Inertia::render('storefront/cart', [
            'cart' => CartResource::make($summary)->resolve(),
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $item = $this->carts->addItem(
            $this->cartSession->currentOrCreate($request),
            (int) $request->validated('product_id'),
            (int) $request->validated('quantity', 1),
        );

        $this->toast(__(':name added to your cart.', ['name' => $item->product->name]));

        return back();
    }

    public function update(UpdateCartItemRequest $request, int $item): RedirectResponse
    {
        $this->carts->updateQuantity($this->cartSession->currentOrCreate($request), $item, (int) $request->validated('quantity'));

        return back();
    }

    public function destroy(Request $request, int $item): RedirectResponse
    {
        $cart = $this->cartSession->current($request);

        if ($cart !== null) {
            $this->carts->removeItem($cart, $item);
        }

        $this->toast(__('Item removed from your cart.'));

        return back();
    }

    public function clear(Request $request): RedirectResponse
    {
        $cart = $this->cartSession->current($request);

        if ($cart !== null) {
            $this->carts->clear($cart);
        }

        return back();
    }
}
