<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Services\CartService;
use App\Services\CartSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CartCouponController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CartSession $cartSession,
    ) {}

    public function store(ApplyCouponRequest $request): RedirectResponse
    {
        $coupon = $this->carts->applyCoupon(
            $this->cartSession->currentOrCreate($request),
            (string) $request->validated('code'),
            $request->user(),
        );

        $this->toast(__('Coupon :code applied.', ['code' => $coupon->code]));

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $cart = $this->cartSession->current($request);

        if ($cart !== null) {
            $this->carts->removeCoupon($cart);
        }

        return back();
    }
}
