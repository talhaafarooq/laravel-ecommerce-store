import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Plus, TicketPercent, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmButton } from '@/components/admin/confirm-button';
import { DataCard } from '@/components/admin/data-card';
import { Field, SwitchField } from '@/components/admin/form';
import { PageHeader } from '@/components/admin/page-header';
import { SearchInput } from '@/components/admin/search-input';
import { SelectField } from '@/components/admin/select-field';
import { EmptyState } from '@/components/shop/empty-state';
import { Pagination } from '@/components/shop/pagination';
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
import { formatDate } from '@/lib/format';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type { Coupon, Option, Paginated } from '@/types';

type Props = {
    coupons: Paginated<Coupon>;
    filters: { search: string | null };
    types: Option[];
};

/** `2026-10-30T12:00:00+00:00` -> `2026-10-30T12:00`, the app's own wall-clock time for a datetime input. */
const toInput = (value: string | null): string =>
    value ? value.slice(0, 16) : '';

export default function Coupons({ coupons, filters, types }: Props) {
    const [editing, setEditing] = useState<Coupon | 'new' | null>(null);
    const apply = useFilters(admin.coupons.index.url(), filters);

    return (
        <>
            <Head title="Coupons" />
            <PageHeader
                title="Coupons"
                description="Discount codes shoppers can apply in their cart."
                actions={
                    <Button onClick={() => setEditing('new')}>
                        <Plus />
                        New coupon
                    </Button>
                }
            />

            <DataCard flush>
                <div className="border-b p-4">
                    <SearchInput
                        value={filters.search}
                        onSearch={(search) => apply({ search })}
                        placeholder="Search codes…"
                        className="w-full sm:w-72"
                    />
                </div>

                {coupons.data.length === 0 ? (
                    <EmptyState
                        icon={TicketPercent}
                        title="No coupons yet"
                        className="m-6 border-0"
                    />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Discount</TableHead>
                                <TableHead>Minimum</TableHead>
                                <TableHead>Used</TableHead>
                                <TableHead>Valid</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {coupons.data.map((coupon) => (
                                <TableRow key={coupon.id}>
                                    <TableCell>
                                        <p className="font-mono font-semibold">
                                            {coupon.code}
                                        </p>
                                        {coupon.description && (
                                            <p className="max-w-56 truncate text-xs text-muted-foreground">
                                                {coupon.description}
                                            </p>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {coupon.summary}
                                        {coupon.max_discount && (
                                            <p className="text-xs text-muted-foreground">
                                                up to{' '}
                                                {coupon.max_discount.formatted}
                                            </p>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {coupon.min_subtotal?.formatted ?? '—'}
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {coupon.times_used}
                                        {coupon.usage_limit
                                            ? ` / ${coupon.usage_limit}`
                                            : ''}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {coupon.starts_at
                                            ? formatDate(coupon.starts_at)
                                            : 'Now'}{' '}
                                        →{' '}
                                        {coupon.expires_at
                                            ? formatDate(coupon.expires_at)
                                            : 'No expiry'}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge tone={coupon.state.tone}>
                                            {coupon.state.label}
                                        </StatusBadge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Edit ${coupon.code}`}
                                                onClick={() =>
                                                    setEditing(coupon)
                                                }
                                            >
                                                <Pencil />
                                            </Button>
                                            <ConfirmButton
                                                title={`Delete ${coupon.code}?`}
                                                description="Orders that used this code keep their discount. This cannot be undone."
                                                onConfirm={() =>
                                                    router.delete(
                                                        admin.coupons.destroy(
                                                            coupon.id,
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
                                                    aria-label={`Delete ${coupon.code}`}
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
                <div className="border-t p-4">
                    <Pagination meta={coupons.meta} />
                </div>
            </DataCard>

            <Dialog
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
            >
                <DialogContent className="max-h-[90svh] overflow-y-auto sm:max-w-xl">
                    {editing !== null && (
                        <CouponForm
                            key={editing === 'new' ? 'new' : editing.id}
                            coupon={editing === 'new' ? null : editing}
                            types={types}
                            onDone={() => setEditing(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

function CouponForm({
    coupon,
    types,
    onDone,
}: {
    coupon: Coupon | null;
    types: Option[];
    onDone: () => void;
}) {
    const action = coupon
        ? admin.coupons.update.form(coupon.id)
        : admin.coupons.store.form();
    const [type, setType] = useState<string>(
        coupon?.type.value ?? 'percentage',
    );

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
                            {coupon ? `Edit ${coupon.code}` : 'New coupon'}
                        </DialogTitle>
                        <DialogDescription>
                            Codes are case-insensitive and upper-cased
                            automatically.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Code" error={errors.code}>
                            {(id) => (
                                <Input
                                    id={id}
                                    name="code"
                                    defaultValue={coupon?.code}
                                    required
                                    maxLength={40}
                                    className="font-mono uppercase"
                                />
                            )}
                        </Field>
                        <Field label="Description" error={errors.description}>
                            {(id) => (
                                <Input
                                    id={id}
                                    name="description"
                                    defaultValue={coupon?.description ?? ''}
                                    maxLength={255}
                                />
                            )}
                        </Field>
                        <Field label="Type" error={errors.type}>
                            {(id) => (
                                <SelectField
                                    id={id}
                                    name="type"
                                    required
                                    defaultValue={type}
                                    options={types}
                                    onChange={setType}
                                />
                            )}
                        </Field>
                        <Field
                            label={
                                type === 'percentage'
                                    ? 'Percent off'
                                    : 'Amount off'
                            }
                            error={errors.value}
                            hint={
                                type === 'percentage'
                                    ? 'Whole number from 1 to 100.'
                                    : undefined
                            }
                        >
                            {(id) => (
                                <Input
                                    id={id}
                                    name="value"
                                    type="number"
                                    step={type === 'percentage' ? '1' : '0.01'}
                                    min={type === 'percentage' ? '1' : '0.01'}
                                    max={
                                        type === 'percentage'
                                            ? '100'
                                            : undefined
                                    }
                                    defaultValue={coupon?.value}
                                    required
                                />
                            )}
                        </Field>
                        {type === 'percentage' && (
                            <Field
                                label="Maximum discount"
                                error={errors.max_discount}
                                hint="Optional cap per order."
                            >
                                {(id) => (
                                    <Input
                                        id={id}
                                        name="max_discount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        defaultValue={
                                            coupon?.max_discount?.decimal ?? ''
                                        }
                                    />
                                )}
                            </Field>
                        )}
                        <Field
                            label="Minimum order"
                            error={errors.min_subtotal}
                        >
                            {(id) => (
                                <Input
                                    id={id}
                                    name="min_subtotal"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    defaultValue={
                                        coupon?.min_subtotal?.decimal ?? ''
                                    }
                                />
                            )}
                        </Field>
                        <Field
                            label="Total uses"
                            error={errors.usage_limit}
                            hint="Blank for unlimited."
                        >
                            {(id) => (
                                <Input
                                    id={id}
                                    name="usage_limit"
                                    type="number"
                                    min="1"
                                    defaultValue={coupon?.usage_limit ?? ''}
                                />
                            )}
                        </Field>
                        <Field
                            label="Uses per customer"
                            error={errors.usage_limit_per_user}
                            hint="Blank for unlimited."
                        >
                            {(id) => (
                                <Input
                                    id={id}
                                    name="usage_limit_per_user"
                                    type="number"
                                    min="1"
                                    defaultValue={
                                        coupon?.usage_limit_per_user ?? ''
                                    }
                                />
                            )}
                        </Field>
                        <Field label="Starts" error={errors.starts_at}>
                            {(id) => (
                                <Input
                                    id={id}
                                    name="starts_at"
                                    type="datetime-local"
                                    defaultValue={toInput(
                                        coupon?.starts_at ?? null,
                                    )}
                                />
                            )}
                        </Field>
                        <Field label="Expires" error={errors.expires_at}>
                            {(id) => (
                                <Input
                                    id={id}
                                    name="expires_at"
                                    type="datetime-local"
                                    defaultValue={toInput(
                                        coupon?.expires_at ?? null,
                                    )}
                                />
                            )}
                        </Field>
                    </div>

                    <SwitchField
                        name="is_active"
                        label="Active"
                        description="Inactive codes are rejected at checkout."
                        defaultChecked={coupon?.is_active ?? true}
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
                            Save coupon
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}

Coupons.layout = withAdminLayout({
    title: 'Coupons',
    href: admin.coupons.index(),
});
