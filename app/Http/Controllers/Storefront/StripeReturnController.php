<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\StripePaymentHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles customers returning from Stripe's hosted checkout page.
 *
 * The webhook remains the source of truth; confirming here only gives the
 * customer an immediate answer when Stripe has already settled the session.
 */
final class StripeReturnController extends Controller
{
    public function __construct(private readonly StripePaymentHandler $stripe) {}

    public function success(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        $sessionId = $request->string('session_id')->toString();

        if ($sessionId !== '' && $this->stripe->confirmReturn($order, $sessionId)) {
            $this->toast(__('Payment received. Thank you for your order!'));

            return to_route('checkout.complete', $order);
        }

        $this->toast(__('We are confirming your payment. Your order will update automatically once it clears.'), 'info');

        return to_route('account.orders.show', $order);
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->authorize('pay', $order);

        $this->stripe->cancelReturn($order);

        $this->toast(__('The payment was cancelled. You can try again from your order page.'), 'error');

        return to_route('account.orders.show', $order);
    }
}
