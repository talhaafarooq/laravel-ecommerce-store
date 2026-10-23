import { Head, Link, router } from '@inertiajs/react';
import { Archive, Pencil, Plus, RotateCcw } from 'lucide-react';
import { ConfirmButton } from '@/components/admin/confirm-button';
import { DataCard } from '@/components/admin/data-card';
import { FilterSelect } from '@/components/admin/filter-select';
import { PageHeader } from '@/components/admin/page-header';
import { SearchInput } from '@/components/admin/search-input';
import { SortableHead } from '@/components/admin/sortable-head';
import { EmptyState } from '@/components/shop/empty-state';
import { Pagination } from '@/components/shop/pagination';
import { ProductImage } from '@/components/shop/product-image';
import { StatusBadge } from '@/components/shop/status-badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import { formatDate } from '@/lib/format';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import { Package } from 'lucide-react';
import type { AdminProduct, CategoryOption, Option, Paginated } from '@/types';

type Filters = {
    search: string | null;
    category: number | null;
    status: string | null;
    stock: string | null;
    sort: string;
    direction: string;
    archived: boolean;
};

type Props = {
    products: Paginated<AdminProduct>;
    filters: Filters;
    categories: CategoryOption[];
    stockStatuses: Option[];
};

export default function ProductsIndex({
    products,
    filters,
    categories,
    stockStatuses,
}: Props) {
    const apply = useFilters(admin.products.index.url(), filters);

    return (
        <>
            <Head title="Products" />
            <PageHeader
                title="Products"
                description="Manage your catalogue, pricing and availability."
                actions={
                    <Button asChild>
                        <Link href={admin.products.create()}>
                            <Plus />
                            New product
                        </Link>
                    </Button>
                }
            />

            <DataCard flush>
                <div className="flex flex-wrap items-center gap-3 border-b p-4">
                    <SearchInput
                        value={filters.search}
                        onSearch={(search) => apply({ search })}
                        placeholder="Search name, SKU or brand…"
                        className="w-full sm:w-72"
                    />
                    <FilterSelect
                        label="Category"
                        allLabel="All categories"
                        value={
                            filters.category ? String(filters.category) : null
                        }
                        onChange={(category) => apply({ category })}
                        options={categories.map((category) => ({
                            value: String(category.id),
                            label: category.parent
                                ? `${category.parent.name} › ${category.name}`
                                : category.name,
                        }))}
                        className="w-52"
                    />
                    <FilterSelect
                        label="Status"
                        allLabel="Any status"
                        value={filters.status}
                        onChange={(status) => apply({ status })}
                        options={[
                            { value: 'active', label: 'Active' },
                            { value: 'inactive', label: 'Inactive' },
                        ]}
                        className="w-36"
                    />
                    <FilterSelect
                        label="Stock"
                        allLabel="Any stock"
                        value={filters.stock}
                        onChange={(stock) => apply({ stock })}
                        options={stockStatuses}
                        className="w-40"
                    />
                    <div className="ml-auto flex items-center gap-2">
                        <Switch
                            id="archived"
                            checked={filters.archived}
                            onCheckedChange={(archived) => apply({ archived })}
                        />
                        <Label
                            htmlFor="archived"
                            className="text-sm font-normal"
                        >
                            Show archived
                        </Label>
                    </div>
                </div>

                {products.data.length === 0 ? (
                    <EmptyState
                        icon={Package}
                        title="No products found"
                        description="Try adjusting your filters or add a new product."
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
                                <TableHead>Category</TableHead>
                                <SortableHead
                                    column="price"
                                    label="Price"
                                    sort={filters.sort}
                                    direction={filters.direction}
                                    onSort={(sort, direction) =>
                                        apply({ sort, direction })
                                    }
                                />
                                <SortableHead
                                    column="stock_quantity"
                                    label="Stock"
                                    sort={filters.sort}
                                    direction={filters.direction}
                                    onSort={(sort, direction) =>
                                        apply({ sort, direction })
                                    }
                                />
                                <SortableHead
                                    column="sales_count"
                                    label="Sold"
                                    sort={filters.sort}
                                    direction={filters.direction}
                                    onSort={(sort, direction) =>
                                        apply({ sort, direction })
                                    }
                                />
                                <TableHead>Status</TableHead>
                                <SortableHead
                                    column="created_at"
                                    label="Added"
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
                                <TableRow
                                    key={product.id}
                                    className={
                                        product.deleted_at ? 'opacity-60' : ''
                                    }
                                >
                                    <TableCell>
                                        <div className="flex items-center gap-3">
                                            <ProductImage
                                                src={product.image}
                                                alt=""
                                                className="size-11 shrink-0 rounded-md border p-1"
                                            />
                                            <div className="min-w-0">
                                                <Link
                                                    href={admin.products.edit(
                                                        product.id,
                                                    )}
                                                    className="block max-w-64 truncate font-medium hover:text-primary"
                                                >
                                                    {product.name}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {product.sku}
                                                    {product.brand &&
                                                        ` · ${product.brand.name}`}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {product.category?.name}
                                    </TableCell>
                                    <TableCell>
                                        <span className="font-medium tabular-nums">
                                            {product.price.formatted}
                                        </span>
                                        {product.compare_at_price && (
                                            <span className="ml-1.5 text-xs text-muted-foreground line-through">
                                                {
                                                    product.compare_at_price
                                                        .formatted
                                                }
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge
                                            tone={product.stock_status.tone}
                                        >
                                            {product.stock_quantity} ·{' '}
                                            {product.stock_status.label}
                                        </StatusBadge>
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {product.sales_count}
                                    </TableCell>
                                    <TableCell>
                                        {product.deleted_at ? (
                                            <StatusBadge tone="neutral">
                                                Archived
                                            </StatusBadge>
                                        ) : product.is_active ? (
                                            <StatusBadge tone="success">
                                                Active
                                            </StatusBadge>
                                        ) : (
                                            <StatusBadge tone="warning">
                                                Inactive
                                            </StatusBadge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {formatDate(product.created_at)}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                asChild
                                            >
                                                <Link
                                                    href={admin.products.edit(
                                                        product.id,
                                                    )}
                                                    aria-label={`Edit ${product.name}`}
                                                >
                                                    <Pencil />
                                                </Link>
                                            </Button>
                                            {product.deleted_at ? (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Restore ${product.name}`}
                                                    onClick={() =>
                                                        router.post(
                                                            admin.products.restore(
                                                                product.id,
                                                            ),
                                                            undefined,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <RotateCcw />
                                                </Button>
                                            ) : (
                                                <ConfirmButton
                                                    title={`Archive “${product.name}”?`}
                                                    description="It will disappear from the storefront and carts. Order history keeps its details, and you can restore it later."
                                                    confirmLabel="Archive"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            admin.products.destroy(
                                                                product.id,
                                                            ),
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Archive ${product.name}`}
                                                    >
                                                        <Archive />
                                                    </Button>
                                                </ConfirmButton>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
                <div className="border-t p-4">
                    <Pagination meta={products.meta} />
                </div>
            </DataCard>
        </>
    );
}

ProductsIndex.layout = withAdminLayout({
    title: 'Products',
    href: admin.products.index(),
});
