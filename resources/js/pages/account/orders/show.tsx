import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CreditCard, Download } from 'lucide-react';
import { useState } from 'react';
import { OrderDetails } from '@/components/shop/order-details';
import { OrderProgress } from '@/components/shop/order-timeline';
import { StatusBadge } from '@/components/shop/status-badge';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { cancel, index, invoice, pay } from '@/routes/account/orders';
import type { Order } from '@/types';

export default function ShowOrder({ order }: { order: Order }) {
    const [paying, setPaying] = useState(false);

    return (
        <>
            <Head title={`Order ${order.number}`} />
            <div className="space-y-6">
                <Link
                    href={index()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    All orders
                </Link>

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex flex-wrap items-center gap-3">
                            <h2 className="text-2xl font-semibold tracking-tight">{order.number}</h2>
                            <StatusBadge tone={order.status.tone}>{order.status.label}</StatusBadge>
                            <StatusBadge tone={order.payment_status.tone}>{order.payment_status.label}</StatusBadge>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">Placed {formatDateTime(order.placed_at)}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {order.can.pay && (
                            <Button
                                disabled={paying}
                                onClick={() =>
                                    router.post(pay(order.number), undefined, {
                                        onStart: () => setPaying(true),
                                        onFinish: () => setPaying(false),
                                    })
                                }
                            >
                                <CreditCard />
                                Pay now
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <a href={invoice.url(order.number)}>
                                <Download />
                                Invoice
                            </a>
                        </Button>
                        {order.can.cancel && (
                            <AlertDialog>
                                <AlertDialogTrigger asChild>
                                    <Button variant="outline" className="text-destructive">
                                        Cancel order
                                    </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Cancel this order?</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            The items will be returned to stock and any discount code will be released.
                                            This cannot be undone.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>Keep order</AlertDialogCancel>
                                        <AlertDialogAction
                                            className="bg-destructive text-white hover:bg-destructive/90"
                                            onClick={() => router.post(cancel(order.number))}
                                        >
                                            Yes, cancel it
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        )}
                    </div>
                </div>

                <div className="rounded-xl border bg-card p-5">
                    <OrderProgress order={order} />
                </div>

                <OrderDetails order={order} />
            </div>
        </>
    );
}
