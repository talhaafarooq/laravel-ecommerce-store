import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Mail, Phone, ShieldCheck } from 'lucide-react';
import { DataCard } from '@/components/admin/data-card';
import { Field, SwitchField } from '@/components/admin/form';
import { PageHeader } from '@/components/admin/page-header';
import { SelectField } from '@/components/admin/select-field';
import { StatCard } from '@/components/shop/stat-card';
import { StatusBadge } from '@/components/shop/status-badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useInitials } from '@/hooks/use-initials';
import { formatDateTime } from '@/lib/format';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import { Package, Wallet } from 'lucide-react';
import type { Address, Option, Order, User } from '@/types';

type Props = {
    user: User;
    addresses: Address[];
    orders: Order[];
    roles: Option[];
};

export default function ShowUser({ user, addresses, orders, roles }: Props) {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const isSelf = auth.user?.id === user.id;

    return (
        <>
            <Head title={user.name} />

            <Link
                href={admin.users.index()}
                className="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft className="size-4" />
                All customers
            </Link>

            <PageHeader
                title={user.name}
                description={`Customer since ${formatDateTime(user.created_at)}`}
                actions={
                    <>
                        <StatusBadge
                            tone={user.is_admin ? 'primary' : 'neutral'}
                        >
                            {user.role.label}
                        </StatusBadge>
                        <StatusBadge
                            tone={user.is_active ? 'success' : 'danger'}
                        >
                            {user.is_active ? 'Active' : 'Deactivated'}
                        </StatusBadge>
                    </>
                }
            />

            <div className="grid gap-6 xl:grid-cols-[22rem_1fr]">
                <div className="space-y-6">
                    <section className="rounded-xl border bg-card p-5">
                        <div className="flex items-center gap-4">
                            <Avatar className="size-16">
                                <AvatarImage
                                    src={user.avatar ?? undefined}
                                    alt=""
                                />
                                <AvatarFallback className="bg-primary/10 text-lg text-primary">
                                    {getInitials(user.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="min-w-0">
                                <p className="truncate font-semibold">
                                    {user.name}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {user.email_verified_at
                                        ? 'Email verified'
                                        : 'Email not verified'}
                                </p>
                            </div>
                        </div>
                        <ul className="mt-5 space-y-3 text-sm">
                            <li className="flex items-center gap-3">
                                <Mail className="size-4 text-muted-foreground" />
                                <a
                                    href={`mailto:${user.email}`}
                                    className="truncate hover:underline"
                                >
                                    {user.email}
                                </a>
                            </li>
                            {user.phone && (
                                <li className="flex items-center gap-3">
                                    <Phone className="size-4 text-muted-foreground" />
                                    {user.phone}
                                </li>
                            )}
                            <li className="flex items-center gap-3">
                                <ShieldCheck className="size-4 text-muted-foreground" />
                                Two-factor{' '}
                                {user.two_factor_enabled ? 'enabled' : 'off'}
                            </li>
                        </ul>
                        <p className="mt-4 text-xs text-muted-foreground">
                            Last login{' '}
                            {user.last_login_at
                                ? formatDateTime(user.last_login_at)
                                : 'never'}
                        </p>
                    </section>

                    <section className="rounded-xl border bg-card p-5">
                        <h2 className="mb-4 font-semibold">Access</h2>
                        <Form
                            {...admin.users.access.form(user.id)}
                            options={{ preserveScroll: true }}
                            className="space-y-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <Field label="Role" error={errors.role}>
                                        {(id) => (
                                            <SelectField
                                                id={id}
                                                name="role"
                                                defaultValue={user.role.value}
                                                options={roles}
                                            />
                                        )}
                                    </Field>
                                    <SwitchField
                                        name="is_active"
                                        label="Account active"
                                        description="Deactivated accounts are signed out and cannot log in."
                                        defaultChecked={user.is_active}
                                    />
                                    {(errors.role || errors.is_active) && (
                                        <p className="text-sm text-destructive">
                                            {errors.role ?? errors.is_active}
                                        </p>
                                    )}
                                    {isSelf && (
                                        <p className="text-xs text-muted-foreground">
                                            This is your own account; you cannot
                                            demote or deactivate yourself.
                                        </p>
                                    )}
                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={processing}
                                    >
                                        {processing && <Spinner />}
                                        Save access
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                </div>

                <div className="space-y-6">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <StatCard
                            label="Orders"
                            value={user.orders_count ?? 0}
                            icon={Package}
                        />
                        <StatCard
                            label="Total spent"
                            value={user.total_spent?.formatted ?? '$0.00'}
                            icon={Wallet}
                        />
                    </div>

                    <DataCard title="Recent orders" flush>
                        {orders.length === 0 ? (
                            <p className="p-8 text-center text-sm text-muted-foreground">
                                No orders yet.
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {orders.map((order) => (
                                    <AdminOrderRow
                                        key={order.id}
                                        order={order}
                                    />
                                ))}
                            </ul>
                        )}
                    </DataCard>

                    <DataCard title="Addresses">
                        {addresses.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No saved addresses.
                            </p>
                        ) : (
                            <ul className="grid gap-4 sm:grid-cols-2">
                                {addresses.map((address) => (
                                    <li
                                        key={address.id}
                                        className="rounded-lg border p-4 text-sm"
                                    >
                                        <p className="mb-1 font-medium">
                                            {address.label ?? 'Address'}
                                            {address.is_default && (
                                                <span className="ml-2 text-xs font-normal text-primary">
                                                    Default
                                                </span>
                                            )}
                                        </p>
                                        <address className="space-y-0.5 text-muted-foreground not-italic">
                                            <p>{address.full_name}</p>
                                            <p>{address.line1}</p>
                                            {address.line2 && (
                                                <p>{address.line2}</p>
                                            )}
                                            <p>
                                                {[
                                                    address.city,
                                                    address.state,
                                                    address.postal_code,
                                                ]
                                                    .filter(Boolean)
                                                    .join(', ')}
                                            </p>
                                            <p>{address.country}</p>
                                        </address>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </DataCard>
                </div>
            </div>
        </>
    );
}

/** Same row as the customer's own list, but linking to the admin order page. */
function AdminOrderRow({ order }: { order: Order }) {
    return (
        <li>
            <Link
                href={admin.orders.show(order.number)}
                className="flex items-center justify-between gap-4 px-5 py-3 transition-colors hover:bg-accent/50"
            >
                <div>
                    <p className="font-medium">{order.number}</p>
                    <p className="text-xs text-muted-foreground">
                        {formatDateTime(order.placed_at)}
                    </p>
                </div>
                <StatusBadge tone={order.status.tone}>
                    {order.status.label}
                </StatusBadge>
                <span className="w-24 text-right font-medium tabular-nums">
                    {order.grand_total.formatted}
                </span>
            </Link>
        </li>
    );
}

ShowUser.layout = withAdminLayout({
    title: 'Customers',
    href: admin.users.index(),
});
