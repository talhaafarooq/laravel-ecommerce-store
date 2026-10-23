import { Head, Link } from '@inertiajs/react';
import { Heart, Package, ShoppingBag, Truck } from 'lucide-react';
import { EmptyState } from '@/components/shop/empty-state';
import { OrderRow } from '@/components/shop/order-row';
import { StatCard } from '@/components/shop/stat-card';
import { Button } from '@/components/ui/button';
import { index as ordersIndex } from '@/routes/account/orders';
import { index as shopIndex } from '@/routes/shop';
import type { Money, Order } from '@/types';

type Props = {
    stats: { orders: number; open: number; spent: Money; wishlist: number };
    recentOrders: Order[];
};

export default function Dashboard({ stats, recentOrders }: Props) {
    return (
        <>
            <Head title="My account" />
            <div className="space-y-8">
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard label="Total orders" value={stats.orders} icon={Package} />
                    <StatCard label="In progress" value={stats.open} icon={Truck} />
                    <StatCard label="Total spent" value={stats.spent.formatted} icon={ShoppingBag} />
                    <StatCard label="Wishlist" value={stats.wishlist} icon={Heart} />
                </div>

                <section>
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Recent orders</h2>
                        {recentOrders.length > 0 && (
                            <Link href={ordersIndex()} className="text-sm font-medium text-primary hover:underline">
                                View all
                            </Link>
                        )}
                    </div>
                    {recentOrders.length === 0 ? (
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
                        <ul className="divide-y rounded-xl border bg-card">
                            {recentOrders.map((order) => (
                                <OrderRow key={order.id} order={order} />
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}
