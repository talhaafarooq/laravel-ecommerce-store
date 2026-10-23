import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    ShoppingBag,
    Tag,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Container } from '@/components/shop/container';
import { EmptyState } from '@/components/shop/empty-state';
import { OrderTotals } from '@/components/shop/order-totals';
import { Price } from '@/components/shop/price';
import { ProductImage } from '@/components/shop/product-image';
import { QuantityInput } from '@/components/shop/quantity-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { login } from '@/routes';
import { clear } from '@/routes/cart';
import { destroy, update } from '@/routes/cart/items';
import {
    destroy as removeCoupon,
    store as applyCoupon,
} from '@/routes/cart/coupon';
import { show as checkout } from '@/routes/checkout';
import { show as productShow } from '@/routes/products';
import { index as shopIndex } from '@/routes/shop';
import type { Cart, CartLine } from '@/types';

export default function CartPage({ cart }: { cart: Cart }) {
    const { auth, store } = usePage().props;

    if (cart.is_empty) {
        return (
            <>
                <Head title="Shopping cart" />
                <Container className="py-16">
                    <EmptyState
                        icon={ShoppingBag}
                        title="Your cart is empty"
                        description="Looks like you haven't added anything yet. Explore the store and find something you love."
                        action={
                            <Button asChild>
                                <Link href={shopIndex()}>Start shopping</Link>
                            </Button>
                        }
                    />
                </Container>
            </>
        );
    }

    const remaining = cart.totals.free_shipping_remaining;
    const threshold = store.free_shipping_threshold;
    const progress =
        threshold && remaining
            ? Math.min(
                  100,
                  ((threshold.amount - remaining.amount) / threshold.amount) *
                      100,
              )
            : 100;

    return (
        <>
            <Head title="Shopping cart" />
            <Container className="py-8 lg:py-12">
                <div className="mb-8 flex items-end justify-between">
                    <h1 className="text-3xl font-semibold tracking-tight">
                        Shopping cart
                    </h1>
                    <button
                        type="button"
                        onClick={() => router.delete(clear())}
                        className="text-sm text-muted-foreground hover:text-destructive"
                    >
                        Clear cart
                    </button>
                </div>

                <div className="grid gap-8 lg:grid-cols-[1fr_24rem]">
                    <div className="space-y-6">
                        {threshold && (
                            <div className="rounded-xl border bg-card p-4">
                                <p className="text-sm">
                                    {remaining && remaining.amount > 0 ? (
                                        <>
                                            Add{' '}
                                            <strong>
                                                {remaining.formatted}
                                            </strong>{' '}
                                            more to get{' '}
                                            <strong>free shipping</strong>!
                                        </>
                                    ) : (
                                        <strong className="text-emerald-600 dark:text-emerald-400">
                                            🎉 You've unlocked free shipping!
                                        </strong>
                                    )}
                                </p>
                                <div className="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                                    <div
                                        className="h-full rounded-full bg-primary transition-all"
                                        style={{ width: `${progress}%` }}
                                    />
                                </div>
                            </div>
                        )}

                        {cart.has_issues && (
                            <div className="flex items-start gap-3 rounded-xl border border-amber-500/40 bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                                <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                Some items need your attention before you can
                                check out.
                            </div>
                        )}

                        <ul className="divide-y rounded-xl border bg-card">
                            {cart.items.map((line) => (
                                <CartRow key={line.id} line={line} />
                            ))}
                        </ul>
                    </div>

                    <aside className="h-fit space-y-5 rounded-xl border bg-card p-6 lg:sticky lg:top-40">
                        <h2 className="text-lg font-semibold">Order summary</h2>
                        <CouponBox coupon={cart.coupon} />
                        <OrderTotals
                            totals={cart.totals}
                            couponCode={cart.coupon?.code}
                        />
                        {auth.user ? (
                            <Button
                                size="lg"
                                className="w-full"
                                asChild
                                disabled={cart.has_issues}
                            >
                                <Link href={checkout()}>
                                    Proceed to checkout
                                    <ArrowRight />
                                </Link>
                            </Button>
                        ) : (
                            <Button size="lg" className="w-full" asChild>
                                <Link href={login()}>Sign in to check out</Link>
                            </Button>
                        )}
                        <Link
                            href={shopIndex()}
                            className="block text-center text-sm text-muted-foreground hover:text-foreground"
                        >
                            or continue shopping
                        </Link>
                    </aside>
                </div>
            </Container>
        </>
    );
}

function CartRow({ line }: { line: CartLine }) {
    const [quantity, setQuantity] = useState(line.quantity);
    const [pending, setPending] = useState(false);

    const change = (next: number) => {
        setQuantity(next);
        router.patch(
            update(line.id),
            { quantity: next },
            {
                preserveScroll: true,
                onStart: () => setPending(true),
                onFinish: () => setPending(false),
                onError: () => setQuantity(line.quantity),
            },
        );
    };

    return (
        <li className="flex gap-4 p-4 sm:p-5">
            <Link href={productShow(line.product.slug)} className="shrink-0">
                <ProductImage
                    src={line.product.image}
                    alt={line.product.name}
                    className="size-24 rounded-lg border p-2 sm:size-28"
                />
            </Link>
            <div className="flex min-w-0 flex-1 flex-col gap-2">
                <div className="flex justify-between gap-3">
                    <div className="min-w-0">
                        <Link
                            href={productShow(line.product.slug)}
                            className="line-clamp-2 font-medium hover:text-primary"
                        >
                            {line.product.name}
                        </Link>
                        <p className="text-xs text-muted-foreground">
                            {line.product.category}
                        </p>
                    </div>
                    <Price price={line.line_total} />
                </div>
                {line.issue && (
                    <p className="flex items-center gap-1.5 text-sm text-destructive">
                        <AlertTriangle className="size-4" />
                        {line.issue}
                    </p>
                )}
                <div className="mt-auto flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <QuantityInput
                            value={quantity}
                            max={Math.max(1, line.max_quantity)}
                            onChange={change}
                            disabled={pending}
                            size="sm"
                        />
                        <span className="text-xs text-muted-foreground">
                            {line.unit_price.formatted} each
                        </span>
                    </div>
                    <button
                        type="button"
                        onClick={() =>
                            router.delete(destroy(line.id), {
                                preserveScroll: true,
                            })
                        }
                        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-destructive"
                    >
                        <Trash2 className="size-4" />
                        Remove
                    </button>
                </div>
            </div>
        </li>
    );
}

function CouponBox({ coupon }: { coupon: Cart['coupon'] }) {
    const form = useForm({ code: '' });

    if (coupon) {
        return (
            <div className="rounded-lg border border-emerald-500/30 bg-emerald-50 p-3 dark:bg-emerald-500/10">
                <div className="flex items-center justify-between gap-2">
                    <p className="flex items-center gap-2 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                        <Tag className="size-4" />
                        {coupon.code}
                    </p>
                    <button
                        type="button"
                        onClick={() =>
                            router.delete(removeCoupon(), {
                                preserveScroll: true,
                            })
                        }
                        aria-label="Remove coupon"
                        className="text-emerald-800 hover:text-emerald-950 dark:text-emerald-300"
                    >
                        <X className="size-4" />
                    </button>
                </div>
                <p className="mt-1 text-xs text-emerald-700 dark:text-emerald-400">
                    {coupon.summary}
                </p>
                {coupon.error && (
                    <p className="mt-1 text-xs text-destructive">
                        {coupon.error}
                    </p>
                )}
            </div>
        );
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post(applyCoupon.url(), {
                    preserveScroll: true,
                    onSuccess: () => form.reset(),
                });
            }}
        >
            <div className="flex gap-2">
                <Input
                    value={form.data.code}
                    onChange={(event) =>
                        form.setData('code', event.target.value)
                    }
                    placeholder="Discount code"
                    aria-label="Discount code"
                    className="uppercase placeholder:normal-case"
                />
                <Button
                    type="submit"
                    variant="outline"
                    disabled={form.processing || !form.data.code.trim()}
                >
                    Apply
                </Button>
            </div>
            <InputError message={form.errors.code} className="mt-2" />
        </form>
    );
}
