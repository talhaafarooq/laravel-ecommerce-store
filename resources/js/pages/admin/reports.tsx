import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
    Download,
    DollarSign,
    Receipt,
    ShoppingCart,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { DataCard } from '@/components/admin/data-card';
import { DeltaBadge } from '@/components/admin/delta-badge';
import { PageHeader } from '@/components/admin/page-header';
import {
    AreaTrendChart,
    compactNumber,
} from '@/components/charts/area-trend-chart';
import { BarList } from '@/components/charts/bar-list';
import { ChartCard } from '@/components/charts/chart-card';
import { StatCard } from '@/components/shop/stat-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import type { Money } from '@/types';

type Report = {
    range: { from: string; to: string };
    summary: {
        orders: number;
        revenue: Money;
        items: number;
        discounts: Money;
        customers: number;
        average_order_value: Money;
        revenue_change: number | null;
        orders_change: number | null;
    };
    daily: { date: string; orders: number; revenue: number }[];
    top_products: {
        product_id: number | null;
        name: string;
        sku: string;
        quantity: number;
        revenue: Money;
    }[];
    categories: { category: string; quantity: number; revenue: Money }[];
    payment_methods: { method: string; orders: number; revenue: Money }[];
};

const presets = [
    { label: 'Last 7 days', days: 7 },
    { label: 'Last 30 days', days: 30 },
    { label: 'Last 90 days', days: 90 },
];

const isoDay = (date: Date): string => {
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);

    return local.toISOString().slice(0, 10);
};

export default function Reports({ report }: { report: Report }) {
    const [from, setFrom] = useState(report.range.from);
    const [to, setTo] = useState(report.range.to);
    const currency = report.summary.revenue.currency;
    const days = report.daily.length;

    const visit = (nextFrom: string, nextTo: string) =>
        router.get(
            admin.reports.index.url(),
            { from: nextFrom, to: nextTo },
            { preserveState: true, preserveScroll: true, replace: true },
        );

    const preset = (count: number) => {
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - (count - 1));
        setFrom(isoDay(start));
        setTo(isoDay(end));
        visit(isoDay(start), isoDay(end));
    };

    const revenueTotal = report.summary.revenue.amount;
    const categoryTotal = report.categories.reduce(
        (sum, row) => sum + row.revenue.amount,
        0,
    );
    const methodTotal = report.payment_methods.reduce(
        (sum, row) => sum + row.revenue.amount,
        0,
    );

    return (
        <>
            <Head title="Sales reports" />
            <PageHeader
                title="Sales reports"
                description="Revenue counts paid orders and cash-on-delivery orders that were not cancelled."
                actions={
                    <Button variant="outline" asChild>
                        <a
                            href={admin.reports.export.url({
                                query: {
                                    from: report.range.from,
                                    to: report.range.to,
                                },
                            })}
                        >
                            <Download />
                            Export CSV
                        </a>
                    </Button>
                }
            />

            {/* One filter row above everything it scopes. */}
            <form
                className="flex flex-wrap items-end gap-3 rounded-xl border bg-card p-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    visit(from, to);
                }}
            >
                <div className="grid gap-1.5">
                    <Label
                        htmlFor="from"
                        className="text-xs text-muted-foreground"
                    >
                        From
                    </Label>
                    <Input
                        id="from"
                        type="date"
                        value={from}
                        max={to}
                        onChange={(event) => setFrom(event.target.value)}
                        className="w-40"
                    />
                </div>
                <div className="grid gap-1.5">
                    <Label
                        htmlFor="to"
                        className="text-xs text-muted-foreground"
                    >
                        To
                    </Label>
                    <Input
                        id="to"
                        type="date"
                        value={to}
                        min={from}
                        max={isoDay(new Date())}
                        onChange={(event) => setTo(event.target.value)}
                        className="w-40"
                    />
                </div>
                <Button type="submit">Apply</Button>
                <div className="ml-auto flex flex-wrap gap-2">
                    {presets.map((item) => (
                        <Button
                            key={item.days}
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => preset(item.days)}
                        >
                            {item.label}
                        </Button>
                    ))}
                </div>
            </form>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Revenue"
                    value={report.summary.revenue.formatted}
                    icon={DollarSign}
                    hint={
                        <DeltaBadge
                            change={report.summary.revenue_change}
                            period={`vs previous ${days} days`}
                        />
                    }
                />
                <StatCard
                    label="Orders"
                    value={formatNumber(report.summary.orders)}
                    icon={ShoppingCart}
                    hint={
                        <DeltaBadge
                            change={report.summary.orders_change}
                            period={`vs previous ${days} days`}
                        />
                    }
                />
                <StatCard
                    label="Average order value"
                    value={report.summary.average_order_value.formatted}
                    icon={Receipt}
                    hint={`${formatNumber(report.summary.items)} items sold · ${report.summary.discounts.formatted} in discounts`}
                />
                <StatCard
                    label="Customers"
                    value={formatNumber(report.summary.customers)}
                    icon={Users}
                    hint="Unique buyers in this period"
                />
            </div>

            <div className="grid gap-6 xl:grid-cols-2">
                <ChartCard
                    title="Revenue"
                    description="Per day"
                    chart={
                        <AreaTrendChart
                            data={report.daily}
                            dataKey="revenue"
                            label="Revenue"
                            formatValue={(value) =>
                                formatCurrency(value, currency)
                            }
                            formatTick={(value) =>
                                `$${compactNumber.format(value)}`
                            }
                        />
                    }
                    table={
                        <DailyTable
                            rows={report.daily}
                            column="revenue"
                            currency={currency}
                        />
                    }
                />
                <ChartCard
                    title="Orders"
                    description="Per day"
                    chart={
                        <AreaTrendChart
                            data={report.daily}
                            dataKey="orders"
                            label="Orders"
                            formatValue={(value) => formatNumber(value)}
                        />
                    }
                    table={
                        <DailyTable
                            rows={report.daily}
                            column="orders"
                            currency={currency}
                        />
                    }
                />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <DataCard
                    title="Sales by department"
                    description="Share of revenue"
                >
                    <BarList
                        total={categoryTotal}
                        items={report.categories.map((row) => ({
                            id: row.category,
                            label: row.category,
                            name: row.category,
                            value: row.revenue.amount,
                            display: row.revenue.formatted,
                            hint: `${formatNumber(row.quantity)} units`,
                        }))}
                    />
                </DataCard>
                <DataCard
                    title="Payment methods"
                    description="Revenue by how customers paid"
                >
                    <BarList
                        total={methodTotal || revenueTotal}
                        items={report.payment_methods.map((row) => ({
                            id: row.method,
                            label: row.method,
                            name: row.method,
                            value: row.revenue.amount,
                            display: row.revenue.formatted,
                            hint: `${formatNumber(row.orders)} orders`,
                        }))}
                    />
                </DataCard>
            </div>

            <DataCard
                title="Top products"
                description="Ranked by revenue"
                flush
            >
                {report.top_products.length === 0 ? (
                    <p className="p-8 text-center text-sm text-muted-foreground">
                        No sales in this period.
                    </p>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">#</TableHead>
                                <TableHead>Product</TableHead>
                                <TableHead className="text-right">
                                    Units
                                </TableHead>
                                <TableHead className="text-right">
                                    Revenue
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {report.top_products.map((product, index) => (
                                <TableRow
                                    key={product.product_id ?? product.sku}
                                >
                                    <TableCell className="text-muted-foreground">
                                        {index + 1}
                                    </TableCell>
                                    <TableCell>
                                        <p className="font-medium">
                                            {product.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {product.sku}
                                        </p>
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatNumber(product.quantity)}
                                    </TableCell>
                                    <TableCell className="text-right font-medium tabular-nums">
                                        {product.revenue.formatted}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </DataCard>
        </>
    );
}

function DailyTable({
    rows,
    column,
    currency,
}: {
    rows: { date: string; orders: number; revenue: number }[];
    column: 'orders' | 'revenue';
    currency: string;
}) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead className="text-right">
                        {column === 'revenue' ? 'Revenue' : 'Orders'}
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {[...rows].reverse().map((row) => (
                    <TableRow key={row.date}>
                        <TableCell>
                            {formatDate(`${row.date}T00:00:00`)}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                            {column === 'revenue'
                                ? formatCurrency(row.revenue, currency)
                                : formatNumber(row.orders)}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

Reports.layout = withAdminLayout({
    title: 'Sales reports',
    href: admin.reports.index(),
});
