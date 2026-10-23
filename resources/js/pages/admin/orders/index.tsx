import { Head, Link } from '@inertiajs/react';
import { ShoppingCart } from 'lucide-react';
import { DataCard } from '@/components/admin/data-card';
import { FilterSelect } from '@/components/admin/filter-select';
import { PageHeader } from '@/components/admin/page-header';
import { SearchInput } from '@/components/admin/search-input';
import { EmptyState } from '@/components/shop/empty-state';
import { Pagination } from '@/components/shop/pagination';
import { StatusBadge } from '@/components/shop/status-badge';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import { formatDate, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type { Option, Order, Paginated } from '@/types';

type Filters = {
    search: string | null;
    status: string | null;
    payment_status: string | null;
    payment_method: string | null;
    from: string | null;
    to: string | null;
};

type Props = {
    orders: Paginated<Order>;
    filters: Filters;
    statusCounts: Record<string, number>;
    options: {
        statuses: Option[];
        payment_statuses: Option[];
        payment_methods: Option[];
    };
};

export default function OrdersIndex({
    orders,
    filters,
    statusCounts,
    options,
}: Props) {
    const apply = useFilters(admin.orders.index.url(), filters);
    const total = Object.values(statusCounts).reduce(
        (sum, count) => sum + count,
        0,
    );

    return (
        <>
            <Head title="Orders" />
            <PageHeader
                title="Orders"
                description="Review, fulfil and track customer orders."
            />

            <DataCard flush>
                <div className="flex gap-1 overflow-x-auto border-b px-4 pt-3">
                    {[
                        { value: null, label: 'All', count: total },
                        ...options.statuses.map((status) => ({
                            ...status,
                            count: statusCounts[status.value] ?? 0,
                        })),
                    ].map((tab) => {
                        const active = filters.status === tab.value;

                        return (
                            <button
                                key={tab.label}
                                type="button"
                                onClick={() => apply({ status: tab.value })}
                                aria-pressed={active}
                                className={cn(
                                    '-mb-px flex shrink-0 items-center gap-2 border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                                    active
                                        ? 'border-primary text-foreground'
                                        : 'border-transparent text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {tab.label}
                                <span className="rounded-full bg-muted px-1.5 py-0.5 text-xs tabular-nums">
                                    {formatNumber(tab.count)}
                                </span>
                            </button>
                        );
                    })}
                </div>

                <div className="flex flex-wrap items-center gap-3 border-b p-4">
                    <SearchInput
                        value={filters.search}
                        onSearch={(search) => apply({ search })}
                        placeholder="Order #, customer, email, phone…"
                        className="w-full sm:w-80"
                    />
                    <FilterSelect
                        label="Payment status"
                        allLabel="Any payment"
                        value={filters.payment_status}
                        onChange={(payment_status) => apply({ payment_status })}
                        options={options.payment_statuses}
                        className="w-40"
                    />
                    <FilterSelect
                        label="Payment method"
                        allLabel="Any method"
                        value={filters.payment_method}
                        onChange={(payment_method) => apply({ payment_method })}
                        options={options.payment_methods}
                        className="w-44"
                    />
                    <div className="flex items-center gap-2">
                        <Input
                            type="date"
                            aria-label="From date"
                            value={filters.from ?? ''}
                            max={filters.to ?? undefined}
                            onChange={(event) =>
                                apply({ from: event.target.value })
                            }
                            className="w-40"
                        />
                        <span className="text-muted-foreground">–</span>
                        <Input
                            type="date"
                            aria-label="To date"
                            value={filters.to ?? ''}
                            min={filters.from ?? undefined}
                            onChange={(event) =>
                                apply({ to: event.target.value })
                            }
                            className="w-40"
                        />
                    </div>
                </div>

                {orders.data.length === 0 ? (
                    <EmptyState
                        icon={ShoppingCart}
                        title="No orders match"
                        description="Try a different status, date range or search term."
                        className="m-6 border-0"
                    />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Order</TableHead>
                                <TableHead>Customer</TableHead>
                                <TableHead>Items</TableHead>
                                <TableHead>Payment</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Total
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {orders.data.map((order) => (
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
                                        <p className="font-medium">
                                            {order.customer?.name ??
                                                order.shipping_address.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {order.customer_email}
                                        </p>
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {order.items_count}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm">
                                                {order.payment_method.label}
                                            </span>
                                            <StatusBadge
                                                tone={order.payment_status.tone}
                                            >
                                                {order.payment_status.label}
                                            </StatusBadge>
                                        </div>
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
                )}
                <div className="border-t p-4">
                    <Pagination meta={orders.meta} />
                </div>
            </DataCard>
        </>
    );
}

OrdersIndex.layout = withAdminLayout({
    title: 'Orders',
    href: admin.orders.index(),
});
