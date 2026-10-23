<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\CancelOrderRequest;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    /**
     * Your orders, newest first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orders->paginateForCustomer($request->user()));
    }

    /**
     * Place an order from your cart.
     *
     * For online payments the response includes a `payment_url` to redirect the customer to.
     */
    public function store(CheckoutRequest $request, CheckoutService $checkout, CartService $carts): JsonResponse
    {
        $user   = $request->user();
        $result = $checkout->checkout($user, $carts->forUser($user), $request->toData());

        return OrderResource::make($this->orders->loadDetails($result->order))
            ->additional(['meta' => [
                'payment_url'   => $result->redirectUrl(),
                'payment_error' => $result->paymentError,
            ]])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        $this->authorize('view', $order);

        return OrderResource::make($this->orders->loadDetails($order));
    }

    public function cancel(CancelOrderRequest $request, Order $order, OrderService $orders): OrderResource
    {
        $orders->cancelByCustomer($order, $request->user(), $request->validated('reason'));

        return OrderResource::make($this->orders->loadDetails($order));
    }
}
