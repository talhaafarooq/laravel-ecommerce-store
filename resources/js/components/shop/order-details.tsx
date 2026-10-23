import { Link } from '@inertiajs/react';
import { OrderTotals } from '@/components/shop/order-totals';
import { ProductImage } from '@/components/shop/product-image';
import { formatDateTime } from '@/lib/format';
import { show } from '@/routes/products';
import type { Order } from '@/types';

/** Items, totals, address and timeline shared by the customer and admin order pages. */
export function OrderDetails({
    order,
    aside,
}: {
    order: Order;
    /** Extra cards rendered above the address (e.g. customer details for admins). */
    aside?: React.ReactNode;
}) {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div className="space-y-6">
                <section className="rounded-xl border bg-card">
                    <h3 className="border-b px-5 py-4 font-semibold">Items</h3>
                    <ul className="divide-y">
                        {order.items?.map((item) => (
                            <li
                                key={item.id}
                                className="flex items-center gap-4 p-4 sm:px-5"
                            >
                                <ProductImage
                                    src={item.image}
                                    alt=""
                                    className="size-16 shrink-0 rounded-lg border p-1"
                                />
                                <div className="min-w-0 flex-1">
                                    {item.product_slug ? (
                                        <Link
                                            href={show(item.product_slug)}
                                            className="line-clamp-2 font-medium hover:text-primary"
                                        >
                                            {item.name}
                                        </Link>
                                    ) : (
                                        <p className="line-clamp-2 font-medium">
                                            {item.name}
                                        </p>
                                    )}
                                    <p className="text-xs text-muted-foreground">
                                        SKU {item.sku}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {item.quantity} ×{' '}
                                        {item.unit_price.formatted}
                                    </p>
                                </div>
                                <p className="font-medium">
                                    {item.line_total.formatted}
                                </p>
                            </li>
                        ))}
                    </ul>
                    <div className="border-t p-5">
                        <OrderTotals
                            couponCode={order.coupon_code}
                            totals={{
                                subtotal: order.subtotal,
                                discount: order.discount_total,
                                shipping: order.shipping_total,
                                tax: order.tax_total,
                                total: order.grand_total,
                                free_shipping_remaining: null,
                            }}
                        />
                    </div>
                </section>

                {order.timeline && order.timeline.length > 0 && (
                    <section className="rounded-xl border bg-card p-5">
                        <h3 className="mb-4 font-semibold">Order history</h3>
                        <ol className="relative space-y-5 border-l pl-6">
                            {[...order.timeline].reverse().map((entry) => (
                                <li key={entry.id} className="relative">
                                    <span className="absolute top-1.5 -left-[1.9rem] size-2.5 rounded-full bg-primary ring-4 ring-background" />
                                    <p className="text-sm font-medium">
                                        {entry.to.label}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {formatDateTime(entry.created_at)}
                                        {entry.actor && ` · ${entry.actor}`}
                                    </p>
                                    {entry.note && (
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {entry.note}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ol>
                    </section>
                )}
            </div>

            <aside className="space-y-6">
                {aside}
                <section className="rounded-xl border bg-card p-5">
                    <h3 className="mb-3 font-semibold">Shipping address</h3>
                    <address className="space-y-0.5 text-sm text-muted-foreground not-italic">
                        <p className="font-medium text-foreground">
                            {order.shipping_address.name}
                        </p>
                        {order.shipping_address.lines.map((line) => (
                            <p key={line}>{line}</p>
                        ))}
                        <p>{order.shipping_address.phone}</p>
                    </address>
                </section>
                <section className="rounded-xl border bg-card p-5">
                    <h3 className="mb-3 font-semibold">Payment</h3>
                    <dl className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <dt className="text-muted-foreground">Method</dt>
                            <dd className="font-medium">
                                {order.payment_method.label}
                            </dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">
                                {order.payment_status.label}
                            </dd>
                        </div>
                        {order.paid_at && (
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Paid on
                                </dt>
                                <dd className="font-medium">
                                    {formatDateTime(order.paid_at)}
                                </dd>
                            </div>
                        )}
                    </dl>
                </section>
                {order.notes && (
                    <section className="rounded-xl border bg-card p-5">
                        <h3 className="mb-2 font-semibold">Notes</h3>
                        <p className="text-sm whitespace-pre-line text-muted-foreground">
                            {order.notes}
                        </p>
                    </section>
                )}
            </aside>
        </div>
    );
}
