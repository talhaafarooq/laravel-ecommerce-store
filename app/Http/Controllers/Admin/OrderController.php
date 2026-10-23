<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderIndexRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function index(OrderIndexRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('admin/orders/index', [
            'orders'       => OrderResource::collection($this->orders->paginateForAdmin($filters)),
            'filters'      => $filters->toArray(),
            'statusCounts' => $this->orders->countByStatus(),
            'options'      => [
                'statuses'         => OrderStatus::options(),
                'payment_statuses' => PaymentStatus::options(),
                'payment_methods'  => PaymentMethod::options(),
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        return Inertia::render('admin/orders/show', [
            'order' => OrderResource::make($this->orders->loadDetails($order))->resolve(),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->updateStatus($order, $request->status(), $request->validated('note'), $request->user());

        $this->toast(__('Order :number is now :status.', ['number' => $order->number, 'status' => $request->status()->label()]));

        return back();
    }

    public function invoice(Order $order, InvoiceService $invoices): HttpResponse
    {
        return $invoices->render($order)->stream($invoices->filename($order));
    }
}
