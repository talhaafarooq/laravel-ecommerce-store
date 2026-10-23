import { Form, Head, router } from '@inertiajs/react';
import { FolderTree, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmButton } from '@/components/admin/confirm-button';
import { DataCard } from '@/components/admin/data-card';
import { Field, SwitchField } from '@/components/admin/form';
import { PageHeader } from '@/components/admin/page-header';
import { SearchInput } from '@/components/admin/search-input';
import { SelectField } from '@/components/admin/select-field';
import { EmptyState } from '@/components/shop/empty-state';
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
import { Textarea } from '@/components/ui/textarea';
import { useFilters } from '@/hooks/use-filters';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type { CategoryOption, CategorySummary } from '@/types';

type Props = {
    categories: CategorySummary[];
    parents: CategoryOption[];
    filters: { search: string | null };
};

export default function Categories({ categories, parents, filters }: Props) {
    const [editing, setEditing] = useState<CategorySummary | 'new' | null>(
        null,
    );
    const apply = useFilters(admin.categories.index.url(), filters);

    return (
        <>
            <Head title="Categories" />
            <PageHeader
                title="Categories"
                description="Organise products into departments and sub-categories."
                actions={
                    <Button onClick={() => setEditing('new')}>
                        <Plus />
                        New category
                    </Button>
                }
            />

            <DataCard flush>
                <div className="border-b p-4">
                    <SearchInput
                        value={filters.search}
                        onSearch={(search) => apply({ search })}
                        placeholder="Search categories…"
                        className="w-full sm:w-72"
                    />
                </div>

                {categories.length === 0 ? (
                    <EmptyState
                        icon={FolderTree}
                        title="No categories found"
                        className="m-6 border-0"
                    />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Parent</TableHead>
                                <TableHead>Products</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Order</TableHead>
                                <TableHead className="text-right">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.map((category) => (
                                <TableRow key={category.id}>
                                    <TableCell>
                                        <div
                                            className={`flex items-center gap-3 ${category.parent_id ? 'pl-6' : ''}`}
                                        >
                                            <span className="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted">
                                                {category.image ? (
                                                    <img
                                                        src={category.image}
                                                        alt=""
                                                        className="size-full object-cover"
                                                    />
                                                ) : (
                                                    <FolderTree className="size-4 text-muted-foreground" />
                                                )}
                                            </span>
                                            <div>
                                                <p className="font-medium">
                                                    {category.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    /{category.slug}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {category.parent?.name ?? '—'}
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {category.products_count}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge
                                            tone={
                                                category.is_active
                                                    ? 'success'
                                                    : 'neutral'
                                            }
                                        >
                                            {category.is_active
                                                ? 'Active'
                                                : 'Hidden'}
                                        </StatusBadge>
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {category.position}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Edit ${category.name}`}
                                                onClick={() =>
                                                    setEditing(category)
                                                }
                                            >
                                                <Pencil />
                                            </Button>
                                            <ConfirmButton
                                                title={`Delete “${category.name}”?`}
                                                description="Categories that still have products or sub-categories cannot be deleted."
                                                onConfirm={() =>
                                                    router.delete(
                                                        admin.categories.destroy(
                                                            category.id,
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
                                                    aria-label={`Delete ${category.name}`}
                                                    className="text-destructive"
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </ConfirmButton>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </DataCard>

            <Dialog
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
            >
                <DialogContent className="max-h-[90svh] overflow-y-auto sm:max-w-xl">
                    {editing !== null && (
                        <CategoryForm
                            key={editing === 'new' ? 'new' : editing.id}
                            category={editing === 'new' ? null : editing}
                            parents={parents}
                            onDone={() => setEditing(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

function CategoryForm({
    category,
    parents,
    onDone,
}: {
    category: CategorySummary | null;
    parents: CategoryOption[];
    onDone: () => void;
}) {
    const action = category
        ? admin.categories.update.form(category.id)
        : admin.categories.store.form();

    return (
        <Form
            {...action}
            options={{ preserveScroll: true }}
            onSuccess={onDone}
            className="space-y-5"
            encType="multipart/form-data"
        >
            {({ processing, errors }) => (
                <>
                    <DialogHeader>
                        <DialogTitle>
                            {category
                                ? `Edit ${category.name}`
                                : 'New category'}
                        </DialogTitle>
                        <DialogDescription>
                            Departments hold sub-categories; products live in
                            sub-categories.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Name" error={errors.name}>
                            {(id) => (
                                <Input
                                    id={id}
                                    name="name"
                                    defaultValue={category?.name}
                                    required
                                    maxLength={120}
                                />
                            )}
                        </Field>
                        <Field
                            label="URL slug"
                            error={errors.slug}
                            hint="Blank to generate."
                        >
                            {(id) => (
                                <Input
                                    id={id}
                                    name="slug"
                                    defaultValue={category?.slug}
                                    maxLength={140}
                                />
                            )}
                        </Field>
                        <Field
                            label="Parent department"
                            error={errors.parent_id}
                        >
                            {(id) => (
                                <SelectField
                                    id={id}
                                    name="parent_id"
                                    emptyLabel="None (top level)"
                                    defaultValue={
                                        category?.parent_id
                                            ? String(category.parent_id)
                                            : null
                                    }
                                    options={parents
                                        .filter(
                                            (parent) =>
                                                parent.id !== category?.id,
                                        )
                                        .map((parent) => ({
                                            value: String(parent.id),
                                            label: parent.name,
                                        }))}
                                />
                            )}
                        </Field>
                        <Field label="Display order" error={errors.position}>
                            {(id) => (
                                <Input
                                    id={id}
                                    name="position"
                                    type="number"
                                    min="0"
                                    defaultValue={category?.position ?? 0}
                                />
                            )}
                        </Field>
                    </div>

                    <Field label="Description" error={errors.description}>
                        {(id) => (
                            <Textarea
                                id={id}
                                name="description"
                                defaultValue={category?.description ?? ''}
                                rows={3}
                                maxLength={2000}
                            />
                        )}
                    </Field>

                    <Field label="Image" error={errors.image}>
                        {(id) => (
                            <Input
                                id={id}
                                name="image"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                            />
                        )}
                    </Field>
                    {category?.image && (
                        <label className="flex items-center gap-2 text-sm text-muted-foreground">
                            <input
                                type="checkbox"
                                name="remove_image"
                                value="1"
                                className="size-4 accent-primary"
                            />
                            Remove current image
                        </label>
                    )}

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Meta title" error={errors.meta_title}>
                            {(id) => (
                                <Input
                                    id={id}
                                    name="meta_title"
                                    defaultValue={category?.meta_title ?? ''}
                                    maxLength={70}
                                />
                            )}
                        </Field>
                        <Field
                            label="Meta description"
                            error={errors.meta_description}
                        >
                            {(id) => (
                                <Input
                                    id={id}
                                    name="meta_description"
                                    defaultValue={
                                        category?.meta_description ?? ''
                                    }
                                    maxLength={160}
                                />
                            )}
                        </Field>
                    </div>

                    <SwitchField
                        name="is_active"
                        label="Visible in the store"
                        description="Hidden categories and their products are removed from the storefront."
                        defaultChecked={category?.is_active ?? true}
                    />

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
                            Save category
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}

Categories.layout = withAdminLayout({
    title: 'Categories',
    href: admin.categories.index(),
});
