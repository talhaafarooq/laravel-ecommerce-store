import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    DollarSign,
    Mail,
    MessageSquareText,
    ShoppingCart,
    TrendingUp,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { DataCard } from '@/components/admin/data-card';
import { DeltaBadge } from '@/components/admin/delta-badge';
import { PageHeader } from '@/components/admin/page-header';
import { SegmentedControl } from '@/components/admin/segmented-control';
import { AreaTrendChart } from '@/components/charts/area-trend-chart';
import { BarList } from '@/components/charts/bar-list';
import { ChartCard } from '@/components/charts/chart-card';
import { ProductImage } from '@/components/shop/product-image';
import { RatingStars } from '@/components/shop/rating-stars';
import { StatCard } from '@/components/shop/stat-card';
import { StatusBadge } from '@/components/shop/status-badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCurrency, formatDate, formatNumber } from '@/lib/format';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type { AdminProduct, Money, Order, Review, Tone } from '@/types';

type Stats = {
    revenue: Money;
    revenue_change: number | null;
    orders: number;
    orders_change: number | null;
    average_order_value: Money;
    customers: number;
    new_customers: number;
    lifetime_revenue: Money;
    active_products: number;
    low_stock: number;
    pending_orders: number;
    pending_reviews: number;
    unread_messages: number;
};

type Props = {
    stats: Stats;
    salesChart: { date: string; revenue: number; orders: number }[];
    statusBreakdown: {
        status: string;
        label: string;
        tone: Tone;
        count: number;
    }[];
    topProducts: {
        product_id: number | null;
        name: string;
        sku: string;
        quantity: number;
        revenue: Money;
    }[];
    recentOrders: Order[];
    lowStock: AdminProduct[];
    latestReviews: Review[];
};

type Metric = 'revenue' | 'orders';

export default function Dashboard({
    stats,
    salesChart,
    statusBreakdown,
    topProducts,
    recentOrders,
    lowStock,
    latestReviews,
}: Props) {
    const [metric, setMetric] = useState<Metric>('revenue');
    const totalOrders = statusBreakdown.reduce(
        (sum, row) => sum + row.count,
        0,
    );

    const attention = [
        {
            label: 'Pending orders',
            value: stats.pending_orders,
            icon: ShoppingCart,
            href: admin.orders.index({ query: { status: 'pending' } }),
        },
        {
            label: 'Low stock',
            value: stats.low_stock,
            icon: AlertTriangle,
            href: admin.inventory.index({ query: { stock: 'low_stock' } }),
        },
        {
            label: 'Reviews to moderate',
            value: stats.pending_reviews,
            icon: MessageSquareText,
            href: admin.reviews.index({ query: { status: 'pending' } }),
        },
        {
            label: 'Unread messages',
            value: stats.unread_messages,
            icon: Mail,
            href: admin.messages.index({ query: { unread: 1 } }),
        },
    ];

    return (
        <>
            <Head title="Dashboard" />

            <PageHeader
                title="Dashboard"
                description="How the store performed over the last 30 days."
            />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Revenue"
                    value={stats.revenue.formatted}
                    icon={DollarSign}
                    hint={<DeltaBadge change={stats.revenue_change} />}
                />
                <StatCard
                    label="Orders"
                    value={formatNumber(stats.orders)}
                    icon={ShoppingCart}
                    hint={<DeltaBadge change={stats.orders_change} />}
                />
                <StatCard
                    label="Average order value"
                    value={stats.average_order_value.formatted}
                    icon={TrendingUp}
                    hint={`Lifetime revenue ${stats.lifetime_revenue.formatted}`}
                />
                <StatCard
                    label="Customers"
                    value={formatNumber(stats.customers)}
                    icon={Users}
                    hint={`${formatNumber(stats.new_customers)} new in the last 30 days`}
                />
            </div>

            <ul className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {attention.map(({ label, value, icon: Icon, href }) => (
                    <li key={label}>
                        <Link
                            href={href}
                            className="flex items-center gap-3 rounded-xl border bg-card p-4 transition-colors hover:bg-accent/50"
                        >
                            <span className="flex size-9 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                <Icon className="size-4" />
                            </span>
                            <span className="flex-1 text-sm">{label}</span>
                            <span className="text-lg font-semibold">
                                {value}
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>

            <div className="grid gap-6 xl:grid-cols-[1fr_22rem]">
                <ChartCard
                    title={metric === 'revenue' ? 'Revenue' : 'Orders'}
                    description="Per day, last 30 days"
                    actions={
                        <SegmentedControl
                            label="Metric"
                            value={metric}
                            onChange={setMetric}
                            options={[
                                { value: 'revenue', label: 'Revenue' },
                                { value: 'orders', label: 'Orders' },
                            ]}
                        />
                    }
                    chart={
                        <AreaTrendChart
                            data={salesChart}
                            dataKey={metric}
                            label={metric === 'revenue' ? 'Revenue' : 'Orders'}
                            formatValue={(value) =>
                                metric === 'revenue'
                                    ? formatCurrency(
                                          value,
                                          stats.revenue.currency,
                                      )
                                    : formatNumber(value)
                            }
                            formatTick={(value) =>
                                metric === 'revenue'
                                    ? `$${new Intl.NumberFormat('en-US', { notation: 'compact' }).format(value)}`
                                    : String(value)
                            }
                        />
                    }
                    table={
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead className="text-right">
                                        Orders
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Revenue
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {[...salesChart].reverse().map((day) => (
                                    <TableRow key={day.date}>
                                        <TableCell>
                                            {formatDate(`${day.date}T00:00:00`)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {day.orders}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatCurrency(
                                                day.revenue,
                                                stats.revenue.currency,
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    }
                />

                <DataCard
                    title="Orders by status"
                    description="Last 30 days and earlier, all time"
                >
                    <BarList
                        total={totalOrders}
                        items={statusBreakdown.map((row) => ({
                            id: row.status,
                            label: row.label,
                            name: `${row.label} orders`,
                            value: row.count,
                            display: formatNumber(row.count),
                            href: admin.orders.index({
                                query: { status: row.status },
                            }),
                        }))}
                    />
                </DataCard>
            </div>

            <div className="grid gap-6 xl:grid-cols-[22rem_1fr]">
                <DataCard
                    title="Top products"
                    description="By revenue, last 30 days"
                >
                    <BarList
                        items={topProducts.map((product) => ({
                            id: product.product_id ?? product.sku,
                            label: product.name,
                            name: product.name,
                            value: product.revenue.amount,
                            display: product.revenue.formatted,
                            hint: `${formatNumber(product.quantity)} sold · ${product.sku}`,
                            href: product.product_id
                                ? admin.products.edit(product.product_id)
                                : undefined,
                        }))}
                    />
                </DataCard>

                <DataCard
                    title="Recent orders"
                    flush
                    actions={
                        <Link
                            href={admin.orders.index()}
                            className="text-sm font-medium text-primary hover:underline"
                        >
                            View all
                        </Link>
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Order</TableHead>
                                <TableHead>Customer</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Total
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {recentOrders.map((order) => (
                                <TableRow key={order.id}>
                                    <TableCell>
                                        <Link
                                            href={admin.orders.show(
                                                order.number,
                                            )}
                                            className="font-medium hover:text-primary"
                                        >
                                            {order.number}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {formatDate(order.placed_at)}
                                        </p>
                                    </TableCell>
                                    <TableCell>
                                        {order.customer?.name ??
                                            order.shipping_address.name}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge tone={order.status.tone}>
                                            {order.status.label}
                                        </StatusBadge>
                                    </TableCell>
                                    <TableCell className="text-right font-medium tabular-nums">
                                        {order.grand_total.formatted}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataCard>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <DataCard
                    title="Running low"
                    description="Active products at or below their reorder point"
                    flush
                    actions={
                        <Link
                            href={admin.inventory.index()}
                            className="text-sm font-medium text-primary hover:underline"
                        >
                            Manage inventory
                        </Link>
                    }
                >
                    {lowStock.length === 0 ? (
                        <p className="p-8 text-center text-sm text-muted-foreground">
                            Everything is well stocked.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {lowStock.map((product) => (
                                <li
                                    key={product.id}
                                    className="flex items-center gap-3 px-5 py-3"
                                >
                                    <ProductImage
                                        src={product.image}
                                        alt=""
                                        className="size-11 shrink-0 rounded-md border p-1"
                                    />
                                    <div className="min-w-0 flex-1">
                                        <Link
                                            href={admin.products.edit(
                                                product.id,
                                            )}
                                            className="block truncate text-sm font-medium hover:text-primary"
                                        >
                                            {product.name}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {product.sku}
                                        </p>
                                    </div>
                                    <StatusBadge
                                        tone={product.stock_status.tone}
                                    >
                                        {product.stock_quantity === 0
                                            ? 'Out of stock'
                                            : `${product.stock_quantity} left`}
                                    </StatusBadge>
                                </li>
                            ))}
                        </ul>
                    )}
                </DataCard>

                <DataCard
                    title="Latest reviews"
                    flush
                    actions={
                        <Link
                            href={admin.reviews.index()}
                            className="text-sm font-medium text-primary hover:underline"
                        >
                            Moderate
                        </Link>
                    }
                >
                    {latestReviews.length === 0 ? (
                        <p className="p-8 text-center text-sm text-muted-foreground">
                            No reviews yet.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {latestReviews.map((review) => (
                                <li key={review.id} className="px-5 py-3">
                                    <div className="flex items-center justify-between gap-3">
                                        <p className="truncate text-sm font-medium">
                                            {review.author.name}
                                        </p>
                                        <RatingStars rating={review.rating} />
                                    </div>
                                    <p className="mt-0.5 line-clamp-2 text-sm text-muted-foreground">
                                        {review.body}
                                    </p>
                                    <p className="mt-1 truncate text-xs text-muted-foreground">
                                        on {review.product?.name}
                                        {!review.is_approved &&
                                            ' · awaiting approval'}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </DataCard>
            </div>
        </>
    );
}

Dashboard.layout = withAdminLayout();
