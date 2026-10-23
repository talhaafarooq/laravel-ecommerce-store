import { Form, Head, Link, router } from '@inertiajs/react';
import { Archive, ImagePlus, Star, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { ConfirmButton } from '@/components/admin/confirm-button';
import { Field, FormSection, SwitchField } from '@/components/admin/form';
import { PageHeader } from '@/components/admin/page-header';
import { SelectField } from '@/components/admin/select-field';
import { ProductImage } from '@/components/shop/product-image';
import { StatusBadge } from '@/components/shop/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import { show } from '@/routes/products';
import type { AdminProduct, CategoryOption, StockMovement } from '@/types';

type Props = {
    product: AdminProduct | null;
    categories: CategoryOption[];
    brands: { id: number; name: string }[];
    stockMovements?: StockMovement[];
};

export default function ProductForm({
    product,
    categories,
    brands,
    stockMovements,
}: Props) {
    const editing = product !== null;
    const action = editing
        ? admin.products.update.form(product.id)
        : admin.products.store.form();

    return (
        <>
            <Head title={editing ? `Edit ${product.name}` : 'New product'} />
            <PageHeader
                title={editing ? product.name : 'New product'}
                description={
                    editing
                        ? `SKU ${product.sku}`
                        : 'Add a product to your catalogue.'
                }
                actions={
                    editing && product.deleted_at ? (
                        <Button
                            variant="outline"
                            onClick={() =>
                                router.post(admin.products.restore(product.id))
                            }
                        >
                            Restore product
                        </Button>
                    ) : undefined
                }
            />

            <div className="grid gap-6 xl:grid-cols-[1fr_22rem]">
                <Form
                    {...action}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                    encType="multipart/form-data"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormSection title="General">
                                <Field label="Name" error={errors.name}>
                                    {(id) => (
                                        <Input
                                            id={id}
                                            name="name"
                                            defaultValue={product?.name}
                                            required
                                            maxLength={200}
                                        />
                                    )}
                                </Field>
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field label="SKU" error={errors.sku}>
                                        {(id) => (
                                            <Input
                                                id={id}
                                                name="sku"
                                                defaultValue={product?.sku}
                                                required
                                                maxLength={64}
                                                className="uppercase"
                                            />
                                        )}
                                    </Field>
                                    <Field
                                        label="URL slug"
                                        error={errors.slug}
                                        hint="Leave blank to generate from the name."
                                    >
                                        {(id) => (
                                            <Input
                                                id={id}
                                                name="slug"
                                                defaultValue={product?.slug}
                                                maxLength={220}
                                            />
                                        )}
                                    </Field>
                                </div>
                                <Field
                                    label="Short description"
                                    error={errors.short_description}
                                >
                                    {(id) => (
                                        <Textarea
                                            id={id}
                                            name="short_description"
                                            defaultValue={
                                                product?.short_description ?? ''
                                            }
                                            rows={2}
                                            maxLength={500}
                                        />
                                    )}
                                </Field>
                                <Field
                                    label="Description"
                                    error={errors.description}
                                    hint="Supports Markdown: **bold**, lists, ## headings and links."
                                >
                                    {(id) => (
                                        <Textarea
                                            id={id}
                                            name="description"
                                            defaultValue={
                                                product?.description ?? ''
                                            }
                                            rows={10}
                                            className="font-mono text-sm"
                                        />
                                    )}
                                </Field>
                            </FormSection>

                            <FormSection title="Pricing & inventory">
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field label="Price" error={errors.price}>
                                        {(id) => (
                                            <Input
                                                id={id}
                                                name="price"
                                                type="number"
                                                inputMode="decimal"
                                                step="0.01"
                                                min="0.01"
                                                defaultValue={
                                                    product?.price.decimal
                                                }
                                                required
                                            />
                                        )}
                                    </Field>
                                    <Field
                                        label="Compare-at price"
                                        error={errors.compare_at_price}
                                        hint="Shown struck through to advertise a discount."
                                    >
                                        {(id) => (
                                            <Input
                                                id={id}
                                                name="compare_at_price"
                                                type="number"
                                                inputMode="decimal"
                                                step="0.01"
                                                min="0"
                                                defaultValue={
                                                    product?.compare_at_price
                                                        ?.decimal ?? ''
                                                }
                                            />
                                        )}
                                    </Field>
                                    <Field
                                        label="Stock on hand"
                                        error={errors.stock_quantity}
                                    >
                                        {(id) => (
                                            <Input
                                                id={id}
                                                name="stock_quantity"
                                                type="number"
                                                min="0"
                                                defaultValue={
                                                    product?.stock_quantity ?? 0
                                                }
                                                required
                                            />
                                        )}
                                    </Field>
                                    <Field
                                        label="Low-stock threshold"
                                        error={errors.low_stock_threshold}
                                        hint="You are alerted at or below this level."
                                    >
                                        {(id) => (
                                            <Input
                                                id={id}
                                                name="low_stock_threshold"
                                                type="number"
                                                min="0"
                                                defaultValue={
                                                    product?.low_stock_threshold ??
                                                    5
                                                }
                                                required
                                            />
                                        )}
                                    </Field>
                                </div>
                            </FormSection>

                            <FormSection title="Organisation">
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field
                                        label="Category"
                                        error={errors.category_id}
                                    >
                                        {(id) => (
                                            <SelectField
                                                id={id}
                                                name="category_id"
                                                required
                                                placeholder="Choose a category"
                                                defaultValue={
                                                    product
                                                        ? String(
                                                              product.category_id,
                                                          )
                                                        : null
                                                }
                                                options={categories
                                                    .filter(
                                                        (category) =>
                                                            category.parent_id !==
                                                            null,
                                                    )
                                                    .map((category) => ({
                                                        value: String(
                                                            category.id,
                                                        ),
                                                        label: category.parent
                                                            ? `${category.parent.name} › ${category.name}`
                                                            : category.name,
                                                    }))}
                                            />
                                        )}
                                    </Field>
                                    <Field
                                        label="Brand"
                                        error={errors.brand_id}
                                    >
                                        {(id) => (
                                            <SelectField
                                                id={id}
                                                name="brand_id"
                                                emptyLabel="No brand"
                                                defaultValue={
                                                    product?.brand_id
                                                        ? String(
                                                              product.brand_id,
                                                          )
                                                        : null
                                                }
                                                options={brands.map(
                                                    (brand) => ({
                                                        value: String(brand.id),
                                                        label: brand.name,
                                                    }),
                                                )}
                                            />
                                        )}
                                    </Field>
                                </div>
                                <SwitchField
                                    name="is_active"
                                    label="Active"
                                    description="Inactive products are hidden from the storefront."
                                    defaultChecked={product?.is_active ?? true}
                                />
                                <SwitchField
                                    name="is_featured"
                                    label="Featured"
                                    description="Highlight this product on the home page."
                                    defaultChecked={
                                        product?.is_featured ?? false
                                    }
                                />
                            </FormSection>

                            <FormSection
                                title="Images"
                                description="JPG, PNG or WebP up to 4 MB. They are optimised and thumbnailed automatically."
                            >
                                {editing &&
                                    product.images &&
                                    product.images.length > 0 && (
                                        <ul className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                            {product.images.map(
                                                (image, index) => (
                                                    <li
                                                        key={image.id}
                                                        className="group relative overflow-hidden rounded-lg border"
                                                    >
                                                        <ProductImage
                                                            src={
                                                                image.thumbnail
                                                            }
                                                            alt={
                                                                image.alt ?? ''
                                                            }
                                                            className="aspect-square p-2"
                                                        />
                                                        {index === 0 && (
                                                            <span className="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-primary px-2 py-0.5 text-[10px] font-semibold text-primary-foreground">
                                                                <Star className="size-3" />
                                                                Primary
                                                            </span>
                                                        )}
                                                        <div className="absolute inset-x-0 bottom-0 flex justify-end gap-1 bg-gradient-to-t from-background/95 to-transparent p-2 opacity-100 sm:opacity-0 sm:transition-opacity sm:group-focus-within:opacity-100 sm:group-hover:opacity-100">
                                                            {index !== 0 && (
                                                                <Button
                                                                    type="button"
                                                                    size="icon"
                                                                    variant="secondary"
                                                                    className="size-8"
                                                                    aria-label="Make primary image"
                                                                    onClick={() =>
                                                                        router.post(
                                                                            admin.products.images.primary(
                                                                                {
                                                                                    product:
                                                                                        product.id,
                                                                                    image: image.id,
                                                                                },
                                                                            ),
                                                                            undefined,
                                                                            {
                                                                                preserveScroll: true,
                                                                            },
                                                                        )
                                                                    }
                                                                >
                                                                    <Star />
                                                                </Button>
                                                            )}
                                                            <Button
                                                                type="button"
                                                                size="icon"
                                                                variant="destructive"
                                                                className="size-8"
                                                                aria-label="Delete image"
                                                                onClick={() =>
                                                                    router.delete(
                                                                        admin.products.images.destroy(
                                                                            {
                                                                                product:
                                                                                    product.id,
                                                                                image: image.id,
                                                                            },
                                                                        ),
                                                                        {
                                                                            preserveScroll: true,
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 />
                                                            </Button>
                                                        </div>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    )}
                                <ImagePicker
                                    error={
                                        errors.images ??
                                        firstError(errors, 'images.')
                                    }
                                />
                            </FormSection>

                            <FormSection
                                title="Search engines"
                                description="Overrides for the page title and snippet."
                            >
                                <Field
                                    label="Meta title"
                                    error={errors.meta_title}
                                    hint="Up to 70 characters."
                                >
                                    {(id) => (
                                        <Input
                                            id={id}
                                            name="meta_title"
                                            defaultValue={
                                                product?.meta_title ?? ''
                                            }
                                            maxLength={70}
                                        />
                                    )}
                                </Field>
                                <Field
                                    label="Meta description"
                                    error={errors.meta_description}
                                    hint="Up to 160 characters."
                                >
                                    {(id) => (
                                        <Textarea
                                            id={id}
                                            name="meta_description"
                                            defaultValue={
                                                product?.meta_description ?? ''
                                            }
                                            rows={2}
                                            maxLength={160}
                                        />
                                    )}
                                </Field>
                            </FormSection>

                            <div className="sticky bottom-0 -mx-4 flex items-center justify-end gap-3 border-t bg-background/95 px-4 py-4 backdrop-blur md:-mx-6 md:px-6">
                                <Button variant="outline" asChild>
                                    <Link href={admin.products.index()}>
                                        Cancel
                                    </Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {editing
                                        ? 'Save changes'
                                        : 'Create product'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                {editing && (
                    <aside className="space-y-6">
                        <section className="rounded-xl border bg-card p-5">
                            <h2 className="mb-3 font-semibold">Overview</h2>
                            <dl className="space-y-2 text-sm">
                                <Row label="Stock">
                                    <StatusBadge
                                        tone={product.stock_status.tone}
                                    >
                                        {product.stock_status.label}
                                    </StatusBadge>
                                </Row>
                                <Row label="Units sold">
                                    {product.sales_count}
                                </Row>
                                <Row label="Rating">
                                    {product.reviews_count > 0
                                        ? `${product.rating} (${product.reviews_count})`
                                        : '—'}
                                </Row>
                                <Row label="Updated">
                                    {formatDateTime(product.updated_at)}
                                </Row>
                            </dl>
                            {!product.deleted_at && (
                                <Button
                                    variant="outline"
                                    className="mt-4 w-full"
                                    asChild
                                >
                                    <a
                                        href={show.url(product.slug)}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        View on storefront
                                    </a>
                                </Button>
                            )}
                            {!product.deleted_at && (
                                <ConfirmButton
                                    title={`Archive “${product.name}”?`}
                                    description="It will disappear from the storefront and carts. Order history keeps its details, and you can restore it later."
                                    confirmLabel="Archive"
                                    onConfirm={() =>
                                        router.delete(
                                            admin.products.destroy(product.id),
                                        )
                                    }
                                >
                                    <Button
                                        variant="ghost"
                                        className="mt-2 w-full text-destructive"
                                    >
                                        <Archive />
                                        Archive product
                                    </Button>
                                </ConfirmButton>
                            )}
                        </section>

                        <section className="rounded-xl border bg-card">
                            <h2 className="border-b px-5 py-4 font-semibold">
                                Stock history
                            </h2>
                            {stockMovements === undefined ? (
                                <p className="p-5 text-sm text-muted-foreground">
                                    Loading…
                                </p>
                            ) : stockMovements.length === 0 ? (
                                <p className="p-5 text-sm text-muted-foreground">
                                    No movements recorded.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {stockMovements.map((movement) => (
                                        <li
                                            key={movement.id}
                                            className="flex items-center gap-3 px-5 py-3 text-sm"
                                        >
                                            <span
                                                className={`w-12 shrink-0 font-semibold tabular-nums ${
                                                    movement.quantity < 0
                                                        ? 'text-destructive'
                                                        : ''
                                                }`}
                                            >
                                                {movement.quantity > 0
                                                    ? '+'
                                                    : ''}
                                                {movement.quantity}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate">
                                                    {movement.type.label}
                                                </p>
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {movement.order_number ??
                                                        movement.note ??
                                                        movement.user ??
                                                        ''}
                                                    {' · '}
                                                    {formatDateTime(
                                                        movement.created_at,
                                                    )}
                                                </p>
                                            </div>
                                            <span className="text-xs text-muted-foreground">
                                                → {movement.balance_after}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </aside>
                )}
            </div>
        </>
    );
}

function Row({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex items-center justify-between gap-3">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">{children}</dd>
        </div>
    );
}

function firstError(
    errors: Record<string, string>,
    prefix: string,
): string | undefined {
    const key = Object.keys(errors).find((candidate) =>
        candidate.startsWith(prefix),
    );

    return key ? errors[key] : undefined;
}

/** File input with thumbnails of the selected images. */
function ImagePicker({ error }: { error?: string }) {
    const [files, setFiles] = useState<File[]>([]);
    const previews = useMemo(
        () => files.map((file) => URL.createObjectURL(file)),
        [files],
    );

    useEffect(
        () => () => previews.forEach((url) => URL.revokeObjectURL(url)),
        [previews],
    );

    return (
        <div className="grid gap-3">
            <label className="flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed p-6 text-center transition-colors focus-within:ring-[3px] focus-within:ring-ring/50 hover:bg-accent/50">
                <ImagePlus className="size-6 text-muted-foreground" />
                <span className="text-sm font-medium">Click to add images</span>
                <span className="text-xs text-muted-foreground">
                    PNG, JPG or WebP
                </span>
                <input
                    type="file"
                    name="images[]"
                    accept="image/png,image/jpeg,image/webp"
                    multiple
                    className="sr-only"
                    onChange={(event) =>
                        setFiles(Array.from(event.target.files ?? []))
                    }
                />
            </label>
            {previews.length > 0 && (
                <ul className="grid grid-cols-3 gap-3 sm:grid-cols-6">
                    {previews.map((url, index) => (
                        <li key={url}>
                            <img
                                src={url}
                                alt={files[index]?.name ?? 'Selected image'}
                                className="aspect-square w-full rounded-md border object-cover"
                            />
                        </li>
                    ))}
                </ul>
            )}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}

ProductForm.layout = withAdminLayout({
    title: 'Products',
    href: admin.products.index(),
});
