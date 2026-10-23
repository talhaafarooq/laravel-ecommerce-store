import { Link, usePage } from '@inertiajs/react';
import { Mail, MapPin, Phone, ShieldCheck, Truck, Undo2 } from 'lucide-react';
import { Container } from '@/components/shop/container';
import { StoreLogo } from '@/components/shop/store-logo';
import { about, contact, faq } from '@/routes';
import { dashboard as accountDashboard } from '@/routes/account';
import { index as ordersIndex } from '@/routes/account/orders';
import { index as wishlistIndex } from '@/routes/account/wishlist';
import { index as blogIndex } from '@/routes/blog';
import { index as cartIndex } from '@/routes/cart';
import { show as categoryShow } from '@/routes/categories';
import { index as shopIndex } from '@/routes/shop';

const perks = [
    {
        icon: Truck,
        title: 'Fast delivery',
        text: 'Dispatched within one business day',
    },
    {
        icon: Undo2,
        title: '30-day returns',
        text: 'Changed your mind? No problem',
    },
    {
        icon: ShieldCheck,
        title: 'Secure checkout',
        text: 'Card payments protected by Stripe',
    },
];

export function StoreFooter() {
    const { navigation, store, name } = usePage().props;

    const columns = [
        {
            title: 'Shop',
            links: [
                { label: 'All products', href: shopIndex.url() },
                ...navigation.slice(0, 5).map((category) => ({
                    label: category.name,
                    href: categoryShow.url(category.slug),
                })),
            ],
        },
        {
            title: 'Account',
            links: [
                { label: 'My account', href: accountDashboard.url() },
                { label: 'Orders', href: ordersIndex.url() },
                { label: 'Wishlist', href: wishlistIndex.url() },
                { label: 'Cart', href: cartIndex.url() },
            ],
        },
        {
            title: 'Company',
            links: [
                { label: 'About us', href: about.url() },
                { label: 'Blog', href: blogIndex.url() },
                { label: 'FAQ', href: faq.url() },
                { label: 'Contact', href: contact.url() },
            ],
        },
    ];

    return (
        <footer className="mt-20 border-t bg-muted/40">
            <Container className="grid gap-6 border-b py-8 sm:grid-cols-3">
                {perks.map(({ icon: Icon, title, text }) => (
                    <div key={title} className="flex items-center gap-4">
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <Icon className="size-5" />
                        </span>
                        <div>
                            <p className="text-sm font-semibold">{title}</p>
                            <p className="text-sm text-muted-foreground">
                                {text}
                            </p>
                        </div>
                    </div>
                ))}
            </Container>

            <Container className="grid gap-10 py-12 lg:grid-cols-[1.4fr_repeat(3,1fr)]">
                <div className="space-y-4">
                    <StoreLogo />
                    <p className="max-w-xs text-sm text-muted-foreground">
                        Quality products from brands you trust, delivered fast
                        with friendly support every step of the way.
                    </p>
                    <ul className="space-y-2 text-sm text-muted-foreground">
                        <li className="flex items-start gap-2">
                            <MapPin className="mt-0.5 size-4 shrink-0" />
                            {store.address}
                        </li>
                        <li className="flex items-center gap-2">
                            <Phone className="size-4" />
                            {store.support_phone}
                        </li>
                        <li className="flex items-center gap-2">
                            <Mail className="size-4" />
                            <a
                                href={`mailto:${store.support_email}`}
                                className="hover:text-foreground"
                            >
                                {store.support_email}
                            </a>
                        </li>
                    </ul>
                </div>
                {columns.map((column) => (
                    <div key={column.title}>
                        <h3 className="mb-4 text-sm font-semibold">
                            {column.title}
                        </h3>
                        <ul className="space-y-2.5 text-sm">
                            {column.links.map((link) => (
                                <li key={link.label}>
                                    <Link
                                        href={link.href}
                                        className="text-muted-foreground hover:text-foreground"
                                    >
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </Container>

            <Container className="flex flex-col items-center justify-between gap-4 border-t py-6 text-sm text-muted-foreground sm:flex-row">
                <p>
                    © {new Date().getFullYear()} {name}. All rights reserved.
                </p>
                <div className="flex items-center gap-4">
                    {Object.entries(store.social).map(([network, url]) => (
                        <a
                            key={network}
                            href={url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="capitalize hover:text-foreground"
                        >
                            {network === 'x' ? 'X' : network}
                        </a>
                    ))}
                </div>
            </Container>
        </footer>
    );
}
