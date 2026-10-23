<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Enums\PaymentMethod;
use App\Exceptions\EmptyCartException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CartSession;
use App\Services\CheckoutService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CartSession $cartSession,
    ) {}

    public function show(Request $request, PaymentGatewayManager $gateways): Response|RedirectResponse
    {
        $user    = $request->user();
        $summary = $this->carts->summarize($this->cartSession->current($request), $user);

        if ($summary->isEmpty()) {
            $this->toast(__('Your cart is empty.'), 'info');

            return to_route('cart.index');
        }

        return Inertia::render('storefront/checkout', [
            'cart'           => CartResource::make($summary)->resolve(),
            'addresses'      => AddressResource::collection($user->addresses()->orderByDesc('is_default')->latest('id')->get())->resolve(),
            'paymentMethods' => array_map(static fn (PaymentMethod $method): array => [
                ...$method->toOption(),
                'description' => $method->description(),
            ], $gateways->availableMethods()),
        ]);
    }

    public function store(CheckoutRequest $request, CheckoutService $checkout): HttpResponse
    {
        $cart = $this->cartSession->current($request) ?? throw new EmptyCartException;

        $result = $checkout->checkout($request->user(), $cart, $request->toData());

        if ($result->paymentError !== null) {
            $this->toast($result->paymentError, 'error');

            return to_route('account.orders.show', $result->order);
        }

        $redirect = $result->redirectUrl();

        if ($redirect !== null) {
            return Inertia::location($redirect);
        }

        return to_route('checkout.complete', $result->order);
    }

    public function complete(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        return Inertia::render('storefront/checkout-complete', [
            'order' => OrderResource::make($order->load('items'))->resolve(),
        ]);
    }
}
