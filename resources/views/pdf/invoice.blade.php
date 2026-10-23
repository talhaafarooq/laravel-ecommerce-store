<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Invoice {{ $order->number }}</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family:
                DejaVu Sans,
                sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 0;
        }
        .page {
            padding: 32px 36px;
        }
        .muted {
            color: #6b7280;
        }
        .brand {
            font-size: 22px;
            font-weight: bold;
            color: #111827;
        }
        .title {
            font-size: 26px;
            font-weight: bold;
            text-align: right;
            color: #111827;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header td {
            vertical-align: top;
        }
        .meta td {
            padding: 2px 0;
        }
        .section {
            margin-top: 28px;
        }
        .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            margin-bottom: 6px;
        }
        .items th {
            background: #f3f4f6;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            color: #374151;
        }
        .items td {
            padding: 9px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .right {
            text-align: right;
        }
        .center {
            text-align: center;
        }
        .totals {
            width: 45%;
            margin-left: 55%;
            margin-top: 14px;
        }
        .totals td {
            padding: 5px 10px;
        }
        .totals .grand td {
            border-top: 2px solid #111827;
            font-size: 14px;
            font-weight: bold;
            padding-top: 9px;
        }
        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-paid {
            background: #dcfce7;
            color: #166534;
        }
        .badge-due {
            background: #fef3c7;
            color: #92400e;
        }
        .footer {
            margin-top: 40px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="page">
        <table class="header">
            <tr>
                <td>
                    <div class="brand">{{ $store['name'] }}</div>
                    <div class="muted">{{ $store['address'] }}</div>
                    <div class="muted">{{ $store['email'] }} &middot; {{ $store['phone'] }}</div>
                </td>
                <td class="right">
                    <div class="title">INVOICE</div>
                    <table class="meta" style="width: auto; margin-left: auto">
                        <tr>
                            <td class="muted right" style="padding-right: 10px">Invoice no.</td>
                            <td class="right"><strong>{{ $order->number }}</strong></td>
                        </tr>
                        <tr>
                            <td class="muted right" style="padding-right: 10px">Date</td>
                            <td class="right">{{ $order->placed_at->format('M j, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="muted right" style="padding-right: 10px">Payment</td>
                            <td class="right">{{ $order->payment_method->label() }}</td>
                        </tr>
                        <tr>
                            <td class="muted right" style="padding-right: 10px">Status</td>
                            <td class="right">
                                <span class="badge {{ $order->isPaid() ? 'badge-paid' : 'badge-due' }}">{{ $order->payment_status->label() }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="section">
            <tr>
                <td style="width: 50%; vertical-align: top">
                    <div class="label">Bill &amp; ship to</div>
                    <strong>{{ $order->shipping_name }}</strong><br />
                    @foreach ($order->shippingAddressLines() as $line)
                        {{ $line }}<br
                         />
                    @endforeach
                    {{ $order->shipping_phone }}<br />
                    {{ $order->customer_email }}
                </td>
                <td style="width: 50%; vertical-align: top">
                    @if ($order->notes)
                        <div class="label">Order notes</div>
                        {{ $order->notes }}
                    @endif
                </td>
            </tr>
        </table>

        <table class="items section">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th class="center">Qty</th>
                    <th class="right">Unit price</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td class="muted">{{ $item->product_sku }}</td>
                        <td class="center">{{ $item->quantity }}</td>
                        <td class="right">{{ $item->unit_price->format() }}</td>
                        <td class="right">{{ $item->line_total->format() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="muted">Subtotal</td>
                <td class="right">{{ $order->subtotal->format() }}</td>
            </tr>
            @if ($order->discount_total->isPositive())
                <tr>
                    <td class="muted">
                        Discount
                        @if ($order->coupon_code) ({{ $order->coupon_code }})@endif
                    </td>
                    <td class="right">-{{ $order->discount_total->format() }}</td>
                </tr>
            @endif
            <tr>
                <td class="muted">Shipping</td>
                <td class="right">
                    {{ $order->shipping_total->isZero() ? 'Free' : $order->shipping_total->format() }}
                </td>
            </tr>
            @if ($order->tax_total->isPositive())
                <tr>
                    <td class="muted">Tax</td>
                    <td class="right">{{ $order->tax_total->format() }}</td>
                </tr>
            @endif
            <tr class="grand">
                <td>Total</td>
                <td class="right">{{ $order->grand_total->format() }}</td>
            </tr>
        </table>

        <div class="footer">
            Thank you for shopping with {{ $store['name'] }}. Questions about this invoice? Contact {{ $store['email'] }}.
        </div>
    </div>
</body>
</html>
