import { Head, Link, router } from '@inertiajs/react';
import { PackageSearch, SlidersHorizontal, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Container } from '@/components/shop/container';
import { EmptyState } from '@/components/shop/empty-state';
import { Pagination } from '@/components/shop/pagination';
import { ProductGrid } from '@/components/shop/product-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Slider } from '@/components/ui/slider';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { show as categoryShow } from '@/routes/categories';
import { index as shopIndex } from '@/routes/shop';
import type {
    Brand,
    CategorySummary,
    Money,
    Option,
    Paginated,
    ProductCard,
    ProductFilters,
} from '@/types';

type Props = {
    products: Paginated<ProductCard>;
    filters: ProductFilters;
    category: CategorySummary | null;
    brands: Brand[];
    priceRange: { min: Money; max: Money };
    categoryCounts: Record<string, number>;
    sortOptions: Option[];
};

type Query = Record<string, string | number | string[] | undefined>;

export default function Shop({
    products,
    filters,
    category,
    brands,
    priceRange,
    sortOptions,
}: Props) {
    const [open, setOpen] = useState(false);
    const title =
        category?.name ??
        (filters.search ? `Results for “${filters.search}”` : 'All products');

    const pathname = category
        ? categoryShow.url(category.slug)
        : shopIndex.url();

    const visit = (changes: Query) => {
        const next: Query = {
            q: filters.search ?? undefined,
            brands: filters.brands.length ? filters.brands : undefined,
            min_price: filters.min_price ?? undefined,
            max_price: filters.max_price ?? undefined,
            min_rating: filters.min_rating ?? undefined,
            in_stock: filters.in_stock ? 1 : undefined,
            on_sale: filters.on_sale ? 1 : undefined,
            sort: filters.sort,
            ...changes,
        };

        const query = Object.fromEntries(
            Object.entries(next).filter(
                ([, value]) =>
                    value !== undefined &&
                    value !== '' &&
                    !(Array.isArray(value) && value.length === 0),
            ),
        );

        router.get(pathname, query, {
            preserveScroll: false,
            preserveState: true,
            only: ['products', 'filters', 'brands', 'priceRange'],
        });
    };

    const activeCount =
        filters.brands.length +
        (filters.min_price || filters.max_price ? 1 : 0) +
        (filters.min_rating ? 1 : 0) +
        (filters.in_stock ? 1 : 0) +
        (filters.on_sale ? 1 : 0);

    const panel = (
        <FilterPanel
            filters={filters}
            brands={brands}
            priceRange={priceRange}
            onChange={visit}
        />
    );

    return (
        <>
            <Head title={title}>
                <meta
                    name="description"
                    content={
                        category?.meta_description ??
                        category?.description ??
                        'Browse our full catalogue with filters for category, brand, price and rating.'
                    }
                />
            </Head>

            <div className="border-b bg-muted/40">
                <Container className="py-8">
                    <nav
                        aria-label="Breadcrumb"
                        className="mb-3 flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground"
                    >
                        <Link href={home()} className="hover:text-foreground">
                            Home
                        </Link>
                        <span aria-hidden>/</span>
                        <Link
                            href={shopIndex()}
                            className={cn(
                                category
                                    ? 'hover:text-foreground'
                                    : 'text-foreground',
                            )}
                        >
                            Shop
                        </Link>
                        {category?.parent && (
                            <>
                                <span aria-hidden>/</span>
                                <Link
                                    href={categoryShow(category.parent.slug)}
                                    className="hover:text-foreground"
                                >
                                    {category.parent.name}
                                </Link>
                            </>
                        )}
                        {category && (
                            <>
                                <span aria-hidden>/</span>
                                <span className="text-foreground">
                                    {category.name}
                                </span>
                            </>
                        )}
                    </nav>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    {category?.description && (
                        <p className="mt-2 max-w-2xl text-muted-foreground">
                            {category.description}
                        </p>
                    )}
                </Container>
            </div>

            <Container className="grid gap-8 py-8 lg:grid-cols-[16rem_1fr]">
                <aside className="hidden lg:block">{panel}</aside>

                <div className="min-w-0">
                    <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <Sheet open={open} onOpenChange={setOpen}>
                                <SheetTrigger asChild>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="lg:hidden"
                                    >
                                        <SlidersHorizontal />
                                        Filters
                                        {activeCount > 0 && (
                                            <Badge className="ml-1">
                                                {activeCount}
                                            </Badge>
                                        )}
                                    </Button>
                                </SheetTrigger>
                                <SheetContent
                                    side="left"
                                    className="w-80 overflow-y-auto"
                                >
                                    <SheetHeader>
                                        <SheetTitle>Filters</SheetTitle>
                                    </SheetHeader>
                                    <div className="px-4 pb-8">{panel}</div>
                                </SheetContent>
                            </Sheet>
                            <p className="text-sm text-muted-foreground">
                                <span className="font-medium text-foreground">
                                    {products.meta.total}
                                </span>{' '}
                                {products.meta.total === 1
                                    ? 'product'
                                    : 'products'}
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            <Label
                                htmlFor="sort"
                                className="hidden text-sm sm:block"
                            >
                                Sort by
                            </Label>
                            <Select
                                value={filters.sort}
                                onValueChange={(sort) => visit({ sort })}
                            >
                                <SelectTrigger
                                    id="sort"
                                    className="w-44"
                                    size="sm"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {sortOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {activeCount > 0 && (
                        <div className="mb-5 flex flex-wrap items-center gap-2">
                            {filters.brands.map((slug) => (
                                <FilterChip
                                    key={slug}
                                    label={
                                        brands.find(
                                            (brand) => brand.slug === slug,
                                        )?.name ?? slug
                                    }
                                    onRemove={() =>
                                        visit({
                                            brands: filters.brands.filter(
                                                (value) => value !== slug,
                                            ),
                                        })
                                    }
                                />
                            ))}
                            {(filters.min_price || filters.max_price) && (
                                <FilterChip
                                    label={`Price: ${filters.min_price ?? 0} – ${filters.max_price ?? '∞'}`}
                                    onRemove={() =>
                                        visit({
                                            min_price: undefined,
                                            max_price: undefined,
                                        })
                                    }
                                />
                            )}
                            {filters.min_rating && (
                                <FilterChip
                                    label={`${filters.min_rating}★ & up`}
                                    onRemove={() =>
                                        visit({ min_rating: undefined })
                                    }
                                />
                            )}
                            {filters.in_stock && (
                                <FilterChip
                                    label="In stock"
                                    onRemove={() =>
                                        visit({ in_stock: undefined })
                                    }
                                />
                            )}
                            {filters.on_sale && (
                                <FilterChip
                                    label="On sale"
                                    onRemove={() =>
                                        visit({ on_sale: undefined })
                                    }
                                />
                            )}
                            <button
                                type="button"
                                className="text-sm text-primary hover:underline"
                                onClick={() =>
                                    visit({
                                        brands: undefined,
                                        min_price: undefined,
                                        max_price: undefined,
                                        min_rating: undefined,
                                        in_stock: undefined,
                                        on_sale: undefined,
                                    })
                                }
                            >
                                Clear all
                            </button>
                        </div>
                    )}

                    {products.data.length === 0 ? (
                        <EmptyState
                            icon={PackageSearch}
                            title="No products found"
                            description="Try removing some filters or searching for something else."
                            action={
                                <Button asChild>
                                    <Link href={shopIndex()}>
                                        Browse all products
                                    </Link>
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <ProductGrid products={products.data} />
                            <Pagination
                                meta={products.meta}
                                className="mt-10"
                            />
                        </>
                    )}
                </div>
            </Container>
        </>
    );
}

function FilterChip({
    label,
    onRemove,
}: {
    label: string;
    onRemove: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onRemove}
            className="inline-flex items-center gap-1 rounded-full border bg-background px-3 py-1 text-xs font-medium hover:bg-accent"
        >
            {label}
            <X className="size-3" />
            <span className="sr-only">Remove filter</span>
        </button>
    );
}

function FilterPanel({
    filters,
    brands,
    priceRange,
    onChange,
}: {
    filters: ProductFilters;
    brands: Brand[];
    priceRange: { min: Money; max: Money };
    onChange: (changes: Query) => void;
}) {
    const floor = Math.floor(Number(priceRange.min.decimal));
    const ceil = Math.max(floor + 1, Math.ceil(Number(priceRange.max.decimal)));
    const current = useMemo<[number, number]>(
        () => [
            filters.min_price ? Number(filters.min_price) : floor,
            filters.max_price ? Number(filters.max_price) : ceil,
        ],
        [filters.min_price, filters.max_price, floor, ceil],
    );
    const [price, setPrice] = useState<[number, number]>(current);

    useEffect(() => setPrice(current), [current]);

    const commitPrice = ([min, max]: number[]) =>
        onChange({
            min_price: min > floor ? min : undefined,
            max_price: max < ceil ? max : undefined,
        });

    return (
        <div className="space-y-8">
            <section>
                <h3 className="mb-4 text-sm font-semibold">Price</h3>
                <Slider
                    min={floor}
                    max={ceil}
                    step={1}
                    value={price}
                    onValueChange={(value) => setPrice([value[0], value[1]])}
                    onValueCommit={commitPrice}
                    aria-label="Price range"
                />
                <div className="mt-3 flex items-center justify-between text-sm text-muted-foreground">
                    <span>${price[0]}</span>
                    <span>${price[1]}</span>
                </div>
            </section>

            <section>
                <h3 className="mb-3 text-sm font-semibold">Customer rating</h3>
                <ul className="space-y-1">
                    {[4, 3, 2].map((rating) => (
                        <li key={rating}>
                            <button
                                type="button"
                                onClick={() =>
                                    onChange({
                                        min_rating:
                                            filters.min_rating === rating
                                                ? undefined
                                                : rating,
                                    })
                                }
                                className={cn(
                                    'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent',
                                    filters.min_rating === rating &&
                                        'bg-primary/10 text-primary',
                                )}
                            >
                                <span className="text-amber-400" aria-hidden>
                                    {'★'.repeat(rating)}
                                    <span className="text-muted-foreground/40">
                                        {'★'.repeat(5 - rating)}
                                    </span>
                                </span>
                                <span>& up</span>
                            </button>
                        </li>
                    ))}
                </ul>
            </section>

            {brands.length > 0 && (
                <section>
                    <h3 className="mb-3 text-sm font-semibold">Brand</h3>
                    <ul className="max-h-64 space-y-2 overflow-y-auto pr-1">
                        {brands.map((brand) => {
                            const checked = filters.brands.includes(brand.slug);

                            return (
                                <li
                                    key={brand.id}
                                    className="flex items-center gap-2"
                                >
                                    <Checkbox
                                        id={`brand-${brand.id}`}
                                        checked={checked}
                                        onCheckedChange={() =>
                                            onChange({
                                                brands: checked
                                                    ? filters.brands.filter(
                                                          (slug) =>
                                                              slug !==
                                                              brand.slug,
                                                      )
                                                    : [
                                                          ...filters.brands,
                                                          brand.slug,
                                                      ],
                                            })
                                        }
                                    />
                                    <Label
                                        htmlFor={`brand-${brand.id}`}
                                        className="flex-1 cursor-pointer text-sm font-normal"
                                    >
                                        {brand.name}
                                    </Label>
                                    <span className="text-xs text-muted-foreground">
                                        {brand.products_count}
                                    </span>
                                </li>
                            );
                        })}
                    </ul>
                </section>
            )}

            <section className="space-y-4">
                <div className="flex items-center justify-between">
                    <Label htmlFor="in-stock" className="text-sm font-normal">
                        In stock only
                    </Label>
                    <Switch
                        id="in-stock"
                        checked={filters.in_stock}
                        onCheckedChange={(checked) =>
                            onChange({ in_stock: checked ? 1 : undefined })
                        }
                    />
                </div>
                <div className="flex items-center justify-between">
                    <Label htmlFor="on-sale" className="text-sm font-normal">
                        On sale
                    </Label>
                    <Switch
                        id="on-sale"
                        checked={filters.on_sale}
                        onCheckedChange={(checked) =>
                            onChange({ on_sale: checked ? 1 : undefined })
                        }
                    />
                </div>
            </section>
        </div>
    );
}
