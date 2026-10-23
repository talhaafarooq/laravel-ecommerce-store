<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddToCartRequest;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The authenticated customer's cart. Every action returns the updated cart.
 */
final class CartController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    public function show(Request $request): CartResource
    {
        return $this->cartResponse($request);
    }

    public function store(AddToCartRequest $request): JsonResponse
    {
        $this->carts->addItem($this->cart($request), (int) $request->validated('product_id'), (int) $request->validated('quantity', 1));

        return $this->cartResponse($request)->response()->setStatusCode(201);
    }

    public function update(UpdateCartItemRequest $request, int $item): CartResource
    {
        $this->carts->updateQuantity($this->cart($request), $item, (int) $request->validated('quantity'));

        return $this->cartResponse($request);
    }

    public function destroy(Request $request, int $item): CartResource
    {
        $this->carts->removeItem($this->cart($request), $item);

        return $this->cartResponse($request);
    }

    public function clear(Request $request): CartResource
    {
        $this->carts->clear($this->cart($request));

        return $this->cartResponse($request);
    }

    public function applyCoupon(ApplyCouponRequest $request): CartResource
    {
        $this->carts->applyCoupon($this->cart($request), (string) $request->validated('code'), $request->user());

        return $this->cartResponse($request);
    }

    public function removeCoupon(Request $request): CartResource
    {
        $this->carts->removeCoupon($this->cart($request));

        return $this->cartResponse($request);
    }

    private function cart(Request $request): Cart
    {
        return $this->carts->forUser($request->user());
    }

    private function cartResponse(Request $request): CartResource
    {
        return CartResource::make($this->carts->summarize($this->cart($request), $request->user()));
    }
}
