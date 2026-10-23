<x-mail::message>
# Thank you for your order!

Hi {{ $order->shipping_name }}, we've received your order **{{ $order->number }}** and will email you as soon as it ships.

<x-mail::table>
| Item | Qty | Total |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ str_replace('|', '/', $item->product_name) }} | {{ $item->quantity }} | {{ $item->line_total->format() }} |
@endforeach
| **Subtotal** | | {{ $order->subtotal->format() }} |
@if ($order->discount_total->isPositive())
| **Discount** ({{ $order->coupon_code }}) | | -{{ $order->discount_total->format() }} |
@endif
| **Shipping** | | {{ $order->shipping_total->isZero() ? 'Free' : $order->shipping_total->format() }} |
@if ($order->tax_total->isPositive())
| **Tax** | | {{ $order->tax_total->format() }} |
@endif
| **Total** | | **{{ $order->grand_total->format() }}** |
</x-mail::table>

**Payment:** {{ $order->payment_method->label() }}

**Shipping to:**<br>
{{ $order->shipping_name }}<br>
@foreach ($order->shippingAddressLines() as $line)
{{ $line }}<br>
@endforeach

<x-mail::button :url="$url">
View your order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
