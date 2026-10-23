<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;

final readonly class InvoiceService
{
    public function __construct(private OrderRepositoryInterface $orders) {}

    public function render(Order $order): PdfDocument
    {
        return Pdf::loadView('pdf.invoice', [
            'order' => $this->orders->loadDetails($order),
            'store' => [
                'name'    => config('app.name'),
                'email'   => config('shop.contact.support_email'),
                'phone'   => config('shop.contact.support_phone'),
                'address' => config('shop.contact.address'),
            ],
        ])->setPaper('a4');
    }

    public function filename(Order $order): string
    {
        return "invoice-{$order->number}.pdf";
    }
}
