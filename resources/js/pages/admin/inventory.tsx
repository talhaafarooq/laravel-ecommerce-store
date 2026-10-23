import { Deferred, Form, Head, Link } from '@inertiajs/react';
import { Boxes, SlidersHorizontal } from 'lucide-react';
import { useState } from 'react';
import { DataCard } from '@/components/admin/data-card';
import { FilterSelect } from '@/components/admin/filter-select';
import { Field } from '@/components/admin/form';
import { PageHeader } from '@/components/admin/page-header';
import { SearchInput } from '@/components/admin/search-input';
import { SelectField } from '@/components/admin/select-field';
import { SortableHead } from '@/components/admin/sortable-head';
import { EmptyState } from '@/components/shop/empty-state';
import { Pagination } from '@/components/shop/pagination';
import { ProductImage } from '@/components/shop/product-image';
import { StatusBadge } from '@/components/shop/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import { formatDateTime } from '@/lib/format';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type {
    AdminProduct,
    CategoryOption,
    Option,
    Paginated,
    StockMovement,
} from '@/types';

type Props = {
    products: Paginated<AdminProduct>;
    filters: {
        search: string | null;
        category: number | null;
        stock: string | null;
        sort: string;
        direction: string;
    };
    categories: CategoryOption[];
    stockStatuses: Option[];
    adjustmentTypes: Option[];
    movements?: StockMovement[];
};

export default function Inventory({
    products,
    filters,
    categories,
    stockStatuses,
    adjustmentTypes,
    movements,
}: Props) {
    const apply = useFilters(admin.inventory.index.url(), filters, [
        'products',
        'filters',
    ]);
    const [adjusting, setAdjusting] = useState<AdminProduct | null>(null);

    return (
        <>
            <Head title="Inventory" />
            <PageHeader
                title="Inventory"
                description="Track stock levels and record every change in the ledger."
            />

            <DataCard flush>
                <div className="flex flex-wrap items-center gap-3 border-b p-4">
                    <SearchInput
                        value={filters.search}
                        onSearch={(search) => apply({ search })}
                        placeholder="Search name or SKU…"
                        className="w-full sm:w-72"
                    />
                    <FilterSelect
                        label="Category"
                        allLabel="All categories"
                        value={
                            filters.category ? String(filters.category) : null
                        }
                        onChange={(category) => apply({ category })}
                        options={categories
                            .filter((category) => category.parent_id !== null)
                            .map((category) => ({
                                value: String(category.id),
                                label: category.parent
                                    ? `${category.parent.name} › ${category.name}`
                                    : category.name,
                            }))}
                        className="w-52"
                    />
                    <FilterSelect
                        label="Stock"
                        allLabel="Any stock level"
                        value={filters.stock}
                        onChange={(stock) => apply({ stock })}
                        options={stockStatuses}
                        className="w-44"
                    />
                </div>

                {products.data.length === 0 ? (
                    <EmptyState
                        icon={Boxes}
                        title="No products match"
                        className="m-6 border-0"
                    />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <SortableHead
                                    column="name"
                                    label="Product"
                                    sort={filters.sort}
                                    direction={filters.direction}
                                    onSort={(sort, direction) =>
                                        apply({ sort, direction })
                                    }
                                />
                                <SortableHead
                                    column="stock_quantity"
                                    label="On hand"
                                    sort={filters.sort}
                                    direction={filters.direction}
                                    onSort={(sort, direction) =>
                                        apply({ sort, direction })
                                    }
                                />
                                <TableHead>Reorder at</TableHead>
                                <TableHead>Status</TableHead>
                                <SortableHead
                                    column="sales_count"
                                    label="Sold"
                                    sort={filters.sort}
                                    direction={filters.direction}
                                    onSort={(sort, direction) =>
                                        apply({ sort, direction })
                                    }
                                />
                                <TableHead className="text-right">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {products.data.map((product) => (
                                <TableRow key={product.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-3">
                                            <ProductImage
                                                src={product.image}
                                                alt=""
                                                className="size-10 shrink-0 rounded-md border p-1"
                                            />
                                            <div className="min-w-0">
                                                <Link
                                                    href={admin.products.edit(
                                                        product.id,
                                                    )}
                                                    className="block max-w-72 truncate font-medium hover:text-primary"
                                                >
                                                    {product.name}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {product.sku}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-lg font-semibold tabular-nums">
                                        {product.stock_quantity}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground tabular-nums">
                                        {product.low_stock_threshold}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge
                                            tone={product.stock_status.tone}
                                        >
                                            {product.stock_status.label}
                                        </StatusBadge>
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {product.sales_count}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                setAdjusting(product)
                                            }
                                        >
                                            <SlidersHorizontal />
                                            Adjust
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
                <div className="border-t p-4">
                    <Pagination
                        meta={products.meta}
                        only={['products', 'filters']}
                    />
                </div>
            </DataCard>

            <DataCard
                title="Recent stock movements"
                description="The latest entries across all products"
                flush
            >
                <Deferred
                    data="movements"
                    fallback={
                        <p className="p-6 text-sm text-muted-foreground">
                            Loading ledger…
                        </p>
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>When</TableHead>
                                <TableHead>Product</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead className="text-right">
                                    Change
                                </TableHead>
                                <TableHead className="text-right">
                                    Balance
                                </TableHead>
                                <TableHead>Reference</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {(movements ?? []).map((movement) => (
                                <TableRow key={movement.id}>
                                    <TableCell className="text-muted-foreground">
                                        {formatDateTime(movement.created_at)}
                                    </TableCell>
                                    <TableCell>
                                        <span className="block max-w-64 truncate font-medium">
                                            {movement.product?.name}
                                        </span>
                                    </TableCell>
                                    <TableCell>{movement.type.label}</TableCell>
                                    <TableCell
                                        className={`text-right font-semibold tabular-nums ${movement.quantity < 0 ? 'text-destructive' : ''}`}
                                    >
                                        {movement.quantity > 0 ? '+' : ''}
                                        {movement.quantity}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {movement.balance_after}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {movement.order_number ??
                                            movement.note ??
                                            movement.user ??
                                            '—'}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </Deferred>
            </DataCard>

            <Dialog
                open={adjusting !== null}
                onOpenChange={(open) => !open && setAdjusting(null)}
            >
                <DialogContent className="sm:max-w-md">
                    {adjusting && (
                        <AdjustForm
                            key={adjusting.id}
                            product={adjusting}
                            types={adjustmentTypes}
                            onDone={() => setAdjusting(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

function AdjustForm({
    product,
    types,
    onDone,
}: {
    product: AdminProduct;
    types: Option[];
    onDone: () => void;
}) {
    return (
        <Form
            {...admin.inventory.adjust.form(product.id)}
            options={{ preserveScroll: true }}
            onSuccess={onDone}
            className="space-y-5"
        >
            {({ processing, errors }) => (
                <>
                    <DialogHeader>
                        <DialogTitle>Adjust stock</DialogTitle>
                        <DialogDescription>
                            {product.name} currently has{' '}
                            <strong>{product.stock_quantity}</strong> on hand.
                        </DialogDescription>
                    </DialogHeader>

                    <Field label="Reason" error={errors.type}>
                        {(id) => (
                            <SelectField
                                id={id}
                                name="type"
                                required
                                defaultValue={types[0]?.value ?? null}
                                options={types}
                            />
                        )}
                    </Field>
                    <Field
                        label="Quantity change"
                        error={errors.quantity}
                        hint="Use a positive number to add stock and a negative number to remove it."
                    >
                        {(id) => (
                            <Input
                                id={id}
                                name="quantity"
                                type="number"
                                step="1"
                                required
                                autoFocus
                                placeholder="e.g. 25 or -3"
                            />
                        )}
                    </Field>
                    <Field label="Note (optional)" error={errors.note}>
                        {(id) => (
                            <Input
                                id={id}
                                name="note"
                                maxLength={255}
                                placeholder="Supplier delivery, damaged units…"
                            />
                        )}
                    </Field>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onDone}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Apply change
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}

Inventory.layout = withAdminLayout({
    title: 'Inventory',
    href: admin.inventory.index(),
});
