import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, Download, Package } from 'lucide-react';
import { Container } from '@/components/shop/container';
import { ProductImage } from '@/components/shop/product-image';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { index as ordersIndex, invoice, show } from '@/routes/account/orders';
import { index as shopIndex } from '@/routes/shop';
import type { Order } from '@/types';

export default function CheckoutComplete({ order }: { order: Order }) {
    return (
        <>
            <Head title="Order confirmed" />
            <Container className="max-w-3xl py-12 lg:py-16">
                <div className="text-center">
                    <span className="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                        <CheckCircle2 className="size-8" />
                    </span>
                    <h1 className="mt-6 text-3xl font-semibold tracking-tight">Thank you for your order!</h1>
                    <p className="mt-2 text-muted-foreground">
                        Order <strong className="text-foreground">{order.number}</strong> was placed on{' '}
                        {formatDate(order.placed_at)}. We've emailed a confirmation to {order.customer_email}.
                    </p>
                </div>

                <div className="mt-10 rounded-xl border bg-card">
                    <ul className="divide-y">
                        {order.items?.map((item) => (
                            <li key={item.id} className="flex items-center gap-4 p-4">
                                <ProductImage src={item.image} alt="" className="size-16 rounded-lg border p-1" />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-medium">{item.name}</p>
                                    <p className="text-sm text-muted-foreground">Qty {item.quantity}</p>
                                </div>
                                <p className="font-medium">{item.line_total.formatted}</p>
                            </li>
                        ))}
                    </ul>
                    <dl className="grid gap-4 border-t bg-muted/30 p-5 text-sm sm:grid-cols-3">
                        <div>
                            <dt className="text-muted-foreground">Payment</dt>
                            <dd className="font-medium">
                                {order.payment_method.label} · {order.payment_status.label}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Ship to</dt>
                            <dd className="font-medium">
                                {order.shipping_address.name}, {order.shipping_address.city}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Total</dt>
                            <dd className="font-semibold">{order.grand_total.formatted}</dd>
                        </div>
                    </dl>
                </div>

                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <Button asChild>
                        <Link href={show(order.number)}>
                            <Package />
                            Track order
                        </Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <a href={invoice.url(order.number)}>
                            <Download />
                            Download invoice
                        </a>
                    </Button>
                    <Button variant="ghost" asChild>
                        <Link href={shopIndex()}>Continue shopping</Link>
                    </Button>
                    <Button variant="ghost" asChild>
                        <Link href={ordersIndex()}>All orders</Link>
                    </Button>
                </div>
            </Container>
        </>
    );
}
