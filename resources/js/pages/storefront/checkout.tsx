import { Head, Link, useForm } from '@inertiajs/react';
import { Banknote, CreditCard, Lock, MapPin } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Container } from '@/components/shop/container';
import { OrderTotals } from '@/components/shop/order-totals';
import { ProductImage } from '@/components/shop/product-image';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { index as cartIndex } from '@/routes/cart';
import { store } from '@/routes/checkout';
import type { Address, Cart, Option } from '@/types';

type PaymentMethod = Option & { description: string };

type Props = {
    cart: Cart;
    addresses: Address[];
    paymentMethods: PaymentMethod[];
};

const blank = {
    full_name: '',
    phone: '',
    line1: '',
    line2: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'US',
};

export default function Checkout({ cart, addresses, paymentMethods }: Props) {
    const initial = addresses.find((address) => address.is_default) ?? addresses[0];
    const [selected, setSelected] = useState<number | 'new'>(initial?.id ?? 'new');

    const form = useForm({
        shipping: initial ? toShipping(initial) : { ...blank },
        payment_method: paymentMethods[0]?.value ?? 'cod',
        notes: '',
        save_address: false,
    });

    const errors = form.errors as Record<string, string | undefined>;

    const pick = (value: string) => {
        if (value === 'new') {
            setSelected('new');
            form.setData('shipping', { ...blank });

            return;
        }

        const address = addresses.find((candidate) => candidate.id === Number(value));

        if (address) {
            setSelected(address.id);
            form.setData('shipping', toShipping(address));
        }
    };

    const setField = (field: keyof typeof blank, value: string) =>
        form.setData('shipping', { ...form.data.shipping, [field]: value });

    return (
        <>
            <Head title="Checkout" />
            <Container className="py-8 lg:py-12">
                <h1 className="mb-8 text-3xl font-semibold tracking-tight">Checkout</h1>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(store.url());
                    }}
                    className="grid gap-8 lg:grid-cols-[1fr_26rem]"
                >
                    <div className="space-y-8">
                        <section className="rounded-xl border bg-card p-6">
                            <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                                <MapPin className="size-5 text-primary" />
                                Shipping address
                            </h2>

                            {addresses.length > 0 && (
                                <RadioGroup
                                    value={String(selected)}
                                    onValueChange={pick}
                                    className="mb-6 grid gap-3 sm:grid-cols-2"
                                >
                                    {addresses.map((address) => (
                                        <Label
                                            key={address.id}
                                            htmlFor={`address-${address.id}`}
                                            className={cn(
                                                'flex cursor-pointer items-start gap-3 rounded-lg border p-4 font-normal transition-colors',
                                                selected === address.id && 'border-primary bg-primary/5',
                                            )}
                                        >
                                            <RadioGroupItem
                                                id={`address-${address.id}`}
                                                value={String(address.id)}
                                                className="mt-1"
                                            />
                                            <span className="text-sm">
                                                <span className="block font-medium">
                                                    {address.label ?? address.full_name}
                                                </span>
                                                <span className="block text-muted-foreground">
                                                    {address.line1}, {address.city}, {address.postal_code}
                                                </span>
                                            </span>
                                        </Label>
                                    ))}
                                    <Label
                                        htmlFor="address-new"
                                        className={cn(
                                            'flex cursor-pointer items-center gap-3 rounded-lg border border-dashed p-4 font-normal',
                                            selected === 'new' && 'border-primary bg-primary/5',
                                        )}
                                    >
                                        <RadioGroupItem id="address-new" value="new" />
                                        <span className="text-sm font-medium">Use a new address</span>
                                    </Label>
                                </RadioGroup>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Full name" error={errors['shipping.full_name']} className="sm:col-span-2">
                                    <Input
                                        value={form.data.shipping.full_name}
                                        onChange={(event) => setField('full_name', event.target.value)}
                                        autoComplete="name"
                                        required
                                    />
                                </Field>
                                <Field label="Phone" error={errors['shipping.phone']} className="sm:col-span-2">
                                    <Input
                                        type="tel"
                                        value={form.data.shipping.phone}
                                        onChange={(event) => setField('phone', event.target.value)}
                                        autoComplete="tel"
                                        required
                                    />
                                </Field>
                                <Field label="Address" error={errors['shipping.line1']} className="sm:col-span-2">
                                    <Input
                                        value={form.data.shipping.line1}
                                        onChange={(event) => setField('line1', event.target.value)}
                                        autoComplete="address-line1"
                                        required
                                    />
                                </Field>
                                <Field label="Apartment, suite, etc. (optional)" error={errors['shipping.line2']} className="sm:col-span-2">
                                    <Input
                                        value={form.data.shipping.line2}
                                        onChange={(event) => setField('line2', event.target.value)}
                                        autoComplete="address-line2"
                                    />
                                </Field>
                                <Field label="City" error={errors['shipping.city']}>
                                    <Input
                                        value={form.data.shipping.city}
                                        onChange={(event) => setField('city', event.target.value)}
                                        autoComplete="address-level2"
                                        required
                                    />
                                </Field>
                                <Field label="State / region" error={errors['shipping.state']}>
                                    <Input
                                        value={form.data.shipping.state}
                                        onChange={(event) => setField('state', event.target.value)}
                                        autoComplete="address-level1"
                                    />
                                </Field>
                                <Field label="Postal code" error={errors['shipping.postal_code']}>
                                    <Input
                                        value={form.data.shipping.postal_code}
                                        onChange={(event) => setField('postal_code', event.target.value)}
                                        autoComplete="postal-code"
                                        required
                                    />
                                </Field>
                                <Field label="Country (2-letter code)" error={errors['shipping.country']}>
                                    <Input
                                        value={form.data.shipping.country}
                                        onChange={(event) => setField('country', event.target.value.toUpperCase())}
                                        maxLength={2}
                                        autoComplete="country"
                                        required
                                    />
                                </Field>
                            </div>

                            {selected === 'new' && (
                                <div className="mt-4 flex items-center gap-2">
                                    <Checkbox
                                        id="save-address"
                                        checked={form.data.save_address}
                                        onCheckedChange={(checked) => form.setData('save_address', checked === true)}
                                    />
                                    <Label htmlFor="save-address" className="font-normal">
                                        Save this address for next time
                                    </Label>
                                </div>
                            )}
                        </section>

                        <section className="rounded-xl border bg-card p-6">
                            <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                                <CreditCard className="size-5 text-primary" />
                                Payment method
                            </h2>
                            <RadioGroup
                                value={form.data.payment_method}
                                onValueChange={(value) => form.setData('payment_method', value)}
                                className="grid gap-3"
                            >
                                {paymentMethods.map((method) => (
                                    <Label
                                        key={method.value}
                                        htmlFor={`pay-${method.value}`}
                                        className={cn(
                                            'flex cursor-pointer items-start gap-3 rounded-lg border p-4 font-normal transition-colors',
                                            form.data.payment_method === method.value && 'border-primary bg-primary/5',
                                        )}
                                    >
                                        <RadioGroupItem id={`pay-${method.value}`} value={method.value} className="mt-1" />
                                        <span className="flex-1">
                                            <span className="block text-sm font-medium">{method.label}</span>
                                            <span className="block text-sm text-muted-foreground">{method.description}</span>
                                        </span>
                                        {method.value === 'stripe' ? (
                                            <CreditCard className="size-5 text-muted-foreground" />
                                        ) : (
                                            <Banknote className="size-5 text-muted-foreground" />
                                        )}
                                    </Label>
                                ))}
                            </RadioGroup>
                            <InputError message={form.errors.payment_method} className="mt-2" />
                        </section>

                        <section className="rounded-xl border bg-card p-6">
                            <Label htmlFor="notes" className="mb-2 block text-lg font-semibold">
                                Order notes <span className="text-sm font-normal text-muted-foreground">(optional)</span>
                            </Label>
                            <Textarea
                                id="notes"
                                value={form.data.notes}
                                onChange={(event) => form.setData('notes', event.target.value)}
                                placeholder="Delivery instructions, gift message…"
                                rows={3}
                                maxLength={1000}
                            />
                            <InputError message={form.errors.notes} className="mt-2" />
                        </section>
                    </div>

                    <aside className="h-fit space-y-5 rounded-xl border bg-card p-6 lg:sticky lg:top-40">
                        <h2 className="text-lg font-semibold">Your order</h2>
                        <ul className="max-h-72 space-y-4 overflow-y-auto pr-1">
                            {cart.items.map((line) => (
                                <li key={line.id} className="flex gap-3">
                                    <div className="relative">
                                        <ProductImage src={line.product.image} alt="" className="size-14 rounded-lg border p-1" />
                                        <span className="absolute -top-2 -right-2 flex size-5 items-center justify-center rounded-full bg-foreground text-[10px] font-semibold text-background">
                                            {line.quantity}
                                        </span>
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="line-clamp-2 text-sm">{line.product.name}</p>
                                    </div>
                                    <p className="text-sm font-medium">{line.line_total.formatted}</p>
                                </li>
                            ))}
                        </ul>
                        <OrderTotals totals={cart.totals} couponCode={cart.coupon?.code} />
                        <Button type="submit" size="lg" className="w-full" disabled={form.processing || cart.has_issues}>
                            {form.processing ? <Spinner /> : <Lock />}
                            {form.data.payment_method === 'stripe'
                                ? `Pay ${cart.totals.total.formatted}`
                                : 'Place order'}
                        </Button>
                        <p className="text-center text-xs text-muted-foreground">
                            By placing your order you agree to our terms. Need to change something?{' '}
                            <Link href={cartIndex()} className="underline">
                                Edit cart
                            </Link>
                        </p>
                    </aside>
                </form>
            </Container>
        </>
    );
}

function toShipping(address: Address): typeof blank {
    return {
        full_name: address.full_name,
        phone: address.phone,
        line1: address.line1,
        line2: address.line2 ?? '',
        city: address.city,
        state: address.state ?? '',
        postal_code: address.postal_code,
        country: address.country,
    };
}

function Field({
    label,
    error,
    className,
    children,
}: {
    label: string;
    error?: string;
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <div className={cn('grid gap-2', className)}>
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
