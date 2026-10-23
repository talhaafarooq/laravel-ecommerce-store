<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Account;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\CancelOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function index(Request $request): Response
    {
        return Inertia::render('account/orders/index', [
            'orders' => OrderResource::collection($this->orders->paginateForCustomer($request->user())),
        ]);
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        return Inertia::render('account/orders/show', [
            'order' => OrderResource::make($this->orders->loadDetails($order))->resolve(),
        ]);
    }

    public function cancel(CancelOrderRequest $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->cancelByCustomer($order, $request->user(), $request->validated('reason'));

        $this->toast(__('Order :number was cancelled.', ['number' => $order->number]));

        return to_route('account.orders.show', $order);
    }

    /**
     * Retry the online payment of an order that has not been paid yet.
     */
    public function pay(Order $order, PaymentService $payments): HttpResponse
    {
        $this->authorize('pay', $order);

        $redirect = $payments->initiate($order)->redirectUrl;

        return $redirect === null
            ? to_route('account.orders.show', $order)
            : Inertia::location($redirect);
    }

    public function invoice(Order $order, InvoiceService $invoices): HttpResponse
    {
        $this->authorize('downloadInvoice', $order);

        return $invoices->render($order)->download($invoices->filename($order));
    }
}
