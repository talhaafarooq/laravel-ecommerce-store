import { Head, Link } from '@inertiajs/react';
import { Package } from 'lucide-react';
import { EmptyState } from '@/components/shop/empty-state';
import { OrderRow } from '@/components/shop/order-row';
import { Pagination } from '@/components/shop/pagination';
import { Button } from '@/components/ui/button';
import { index as shopIndex } from '@/routes/shop';
import type { Order, Paginated } from '@/types';

export default function Orders({ orders }: { orders: Paginated<Order> }) {
    return (
        <>
            <Head title="My orders" />
            <h2 className="mb-6 text-xl font-semibold">Order history</h2>
            {orders.data.length === 0 ? (
                <EmptyState
                    icon={Package}
                    title="No orders yet"
                    description="When you place an order it will show up here."
                    action={
                        <Button asChild>
                            <Link href={shopIndex()}>Start shopping</Link>
                        </Button>
                    }
                />
            ) : (
                <>
                    <ul className="divide-y rounded-xl border bg-card">
                        {orders.data.map((order) => (
                            <OrderRow key={order.id} order={order} />
                        ))}
                    </ul>
                    <Pagination meta={orders.meta} className="mt-6" />
                </>
            )}
        </>
    );
}
