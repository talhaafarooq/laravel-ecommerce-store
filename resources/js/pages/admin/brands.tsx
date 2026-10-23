import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Plus, Tag, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmButton } from '@/components/admin/confirm-button';
import { DataCard } from '@/components/admin/data-card';
import { Field, SwitchField } from '@/components/admin/form';
import { PageHeader } from '@/components/admin/page-header';
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
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type { Brand } from '@/types';

export default function Brands({ brands }: { brands: Brand[] }) {
    const [editing, setEditing] = useState<Brand | 'new' | null>(null);

    return (
        <>
            <Head title="Brands" />
            <PageHeader
                title="Brands"
                description="Brands appear as a filter in the shop."
                actions={
                    <Button onClick={() => setEditing('new')}>
                        <Plus />
                        New brand
                    </Button>
                }
            />

            <DataCard flush>
                {brands.length === 0 ? (
                    <EmptyState
                        icon={Tag}
                        title="No brands yet"
                        className="m-6 border-0"
                    />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Products</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {brands.map((brand) => (
                                <TableRow key={brand.id}>
                                    <TableCell>
                                        <p className="font-medium">
                                            {brand.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            /{brand.slug}
                                        </p>
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {brand.products_count}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge
                                            tone={
                                                brand.is_active
                                                    ? 'success'
                                                    : 'neutral'
                                            }
                                        >
                                            {brand.is_active
                                                ? 'Active'
                                                : 'Hidden'}
                                        </StatusBadge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Edit ${brand.name}`}
                                                onClick={() =>
                                                    setEditing(brand)
                                                }
                                            >
                                                <Pencil />
                                            </Button>
                                            <ConfirmButton
                                                title={`Delete “${brand.name}”?`}
                                                description="Its products stay in the catalogue without a brand."
                                                onConfirm={() =>
                                                    router.delete(
                                                        admin.brands.destroy(
                                                            brand.id,
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
                                                    aria-label={`Delete ${brand.name}`}
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
                <DialogContent className="sm:max-w-lg">
                    {editing !== null && (
                        <BrandForm
                            key={editing === 'new' ? 'new' : editing.id}
                            brand={editing === 'new' ? null : editing}
                            onDone={() => setEditing(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

function BrandForm({
    brand,
    onDone,
}: {
    brand: Brand | null;
    onDone: () => void;
}) {
    const action = brand
        ? admin.brands.update.form(brand.id)
        : admin.brands.store.form();

    return (
        <Form
            {...action}
            options={{ preserveScroll: true }}
            onSuccess={onDone}
            className="space-y-5"
        >
            {({ processing, errors }) => (
                <>
                    <DialogHeader>
                        <DialogTitle>
                            {brand ? `Edit ${brand.name}` : 'New brand'}
                        </DialogTitle>
                        <DialogDescription>
                            Shoppers can filter the catalogue by brand.
                        </DialogDescription>
                    </DialogHeader>
                    <Field label="Name" error={errors.name}>
                        {(id) => (
                            <Input
                                id={id}
                                name="name"
                                defaultValue={brand?.name}
                                required
                                maxLength={120}
                            />
                        )}
                    </Field>
                    <Field label="Description" error={errors.description}>
                        {(id) => (
                            <Textarea
                                id={id}
                                name="description"
                                defaultValue={brand?.description ?? ''}
                                rows={3}
                                maxLength={1000}
                            />
                        )}
                    </Field>
                    <SwitchField
                        name="is_active"
                        label="Visible in filters"
                        defaultChecked={brand?.is_active ?? true}
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
                            Save brand
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}

Brands.layout = withAdminLayout({
    title: 'Brands',
    href: admin.brands.index(),
});
