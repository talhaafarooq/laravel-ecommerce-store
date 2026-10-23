import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { StatusBadge } from '@/components/shop/status-badge';
import { formatDate } from '@/lib/format';
import { show } from '@/routes/account/orders';
import type { Order } from '@/types';

export function OrderRow({ order }: { order: Order }) {
    return (
        <li>
            <Link
                href={show(order.number)}
                className="flex items-center gap-4 p-4 transition-colors hover:bg-accent/50 sm:p-5"
            >
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <p className="font-medium">{order.number}</p>
                        <StatusBadge tone={order.status.tone}>
                            {order.status.label}
                        </StatusBadge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Placed {formatDate(order.placed_at)} ·{' '}
                        {order.payment_method.label}
                        {order.items_count !== undefined &&
                            ` · ${order.items_count} items`}
                    </p>
                </div>
                <p className="font-semibold">{order.grand_total.formatted}</p>
                <ChevronRight className="size-4 text-muted-foreground" />
            </Link>
        </li>
    );
}
