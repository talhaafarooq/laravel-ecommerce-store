import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download } from 'lucide-react';
import { Field } from '@/components/admin/form';
import { PageHeader } from '@/components/admin/page-header';
import { SelectField } from '@/components/admin/select-field';
import { OrderDetails } from '@/components/shop/order-details';
import { OrderProgress } from '@/components/shop/order-timeline';
import { StatusBadge } from '@/components/shop/status-badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type { Order } from '@/types';

export default function ShowOrder({ order }: { order: Order }) {
    return (
        <>
            <Head title={`Order ${order.number}`} />

            <Link
                href={admin.orders.index()}
                className="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft className="size-4" />
                All orders
            </Link>

            <PageHeader
                title={order.number}
                description={`Placed ${formatDateTime(order.placed_at)}`}
                actions={
                    <>
                        <StatusBadge tone={order.status.tone}>
                            {order.status.label}
                        </StatusBadge>
                        <StatusBadge tone={order.payment_status.tone}>
                            {order.payment_status.label}
                        </StatusBadge>
                        <Button variant="outline" asChild>
                            <a
                                href={admin.orders.invoice.url(order.number)}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Download />
                                Invoice
                            </a>
                        </Button>
                    </>
                }
            />

            <div className="rounded-xl border bg-card p-5">
                <OrderProgress order={order} />
            </div>

            <OrderDetails
                order={order}
                aside={
                    <>
                        {order.allowed_transitions.length > 0 && (
                            <section className="rounded-xl border bg-card p-5">
                                <h3 className="mb-3 font-semibold">
                                    Update status
                                </h3>
                                <Form
                                    {...admin.orders.status.form(order.number)}
                                    options={{ preserveScroll: true }}
                                    resetOnSuccess
                                    className="space-y-4"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <Field
                                                label="Move to"
                                                error={errors.status}
                                            >
                                                {(id) => (
                                                    <SelectField
                                                        id={id}
                                                        name="status"
                                                        required
                                                        placeholder="Choose the next status"
                                                        options={
                                                            order.allowed_transitions
                                                        }
                                                    />
                                                )}
                                            </Field>
                                            <Field
                                                label="Note (optional)"
                                                error={errors.note}
                                                hint="Visible in the order history."
                                            >
                                                {(id) => (
                                                    <Textarea
                                                        id={id}
                                                        name="note"
                                                        rows={2}
                                                        maxLength={500}
                                                    />
                                                )}
                                            </Field>
                                            <Button
                                                type="submit"
                                                className="w-full"
                                                disabled={processing}
                                            >
                                                {processing && <Spinner />}
                                                Update status
                                            </Button>
                                            <p className="text-xs text-muted-foreground">
                                                Cancelling returns the items to
                                                stock. Customers are emailed
                                                about every change.
                                            </p>
                                        </>
                                    )}
                                </Form>
                            </section>
                        )}

                        <section className="rounded-xl border bg-card p-5">
                            <h3 className="mb-3 font-semibold">Customer</h3>
                            {order.customer ? (
                                <div className="space-y-0.5 text-sm">
                                    <Link
                                        href={admin.users.show(
                                            order.customer.id,
                                        )}
                                        className="font-medium hover:text-primary"
                                    >
                                        {order.customer.name}
                                    </Link>
                                    <p className="text-muted-foreground">
                                        {order.customer.email}
                                    </p>
                                    {order.customer.phone && (
                                        <p className="text-muted-foreground">
                                            {order.customer.phone}
                                        </p>
                                    )}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    The customer account was deleted.{' '}
                                    {order.customer_email}
                                </p>
                            )}
                        </section>

                        {order.payments && order.payments.length > 0 && (
                            <section className="rounded-xl border bg-card p-5">
                                <h3 className="mb-3 font-semibold">
                                    Payment attempts
                                </h3>
                                <ul className="space-y-3 text-sm">
                                    {order.payments.map((payment) => (
                                        <li
                                            key={payment.id}
                                            className="rounded-lg bg-muted/40 p-3"
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="font-medium">
                                                    {payment.amount.formatted}
                                                </span>
                                                <StatusBadge
                                                    tone={
                                                        payment.status.value ===
                                                        'succeeded'
                                                            ? 'success'
                                                            : payment.status
                                                                    .value ===
                                                                'failed'
                                                              ? 'danger'
                                                              : 'warning'
                                                    }
                                                >
                                                    {payment.status.label}
                                                </StatusBadge>
                                            </div>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {payment.gateway.label} ·{' '}
                                                {formatDateTime(
                                                    payment.created_at,
                                                )}
                                            </p>
                                            {payment.reference && (
                                                <p className="mt-1 text-xs break-all text-muted-foreground">
                                                    Ref {payment.reference}
                                                </p>
                                            )}
                                            {payment.failure_reason && (
                                                <p className="mt-1 text-xs text-destructive">
                                                    {payment.failure_reason}
                                                </p>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}
                    </>
                }
            />
        </>
    );
}

ShowOrder.layout = withAdminLayout({
    title: 'Orders',
    href: admin.orders.index(),
});
