import { Link, usePage } from '@inertiajs/react';
import {
    Heart,
    LayoutDashboard,
    MapPin,
    Package,
    Palette,
    ShieldCheck,
    UserRound,
} from 'lucide-react';
import { Container } from '@/components/shop/container';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/account';
import { index as addressesIndex } from '@/routes/account/addresses';
import { index as ordersIndex } from '@/routes/account/orders';
import { index as wishlistIndex } from '@/routes/account/wishlist';
import { edit as appearanceEdit } from '@/routes/appearance';
import { edit as profileEdit } from '@/routes/profile';
import { edit as securityEdit } from '@/routes/security';

const items = [
    { title: 'Overview', href: dashboard.url(), icon: LayoutDashboard, exact: true },
    { title: 'Orders', href: ordersIndex.url(), icon: Package },
    { title: 'Wishlist', href: wishlistIndex.url(), icon: Heart },
    { title: 'Addresses', href: addressesIndex.url(), icon: MapPin },
    { title: 'Profile', href: profileEdit.url(), icon: UserRound },
    { title: 'Security', href: securityEdit.url(), icon: ShieldCheck },
    { title: 'Appearance', href: appearanceEdit.url(), icon: Palette },
];

export default function AccountLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { auth } = usePage().props;
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <Container className="py-8 lg:py-12">
            <div className="mb-8">
                <p className="text-sm text-muted-foreground">My account</p>
                <h1 className="text-2xl font-semibold tracking-tight">
                    Hello, {auth.user?.name.split(' ')[0]}
                </h1>
            </div>
            <div className="grid gap-8 lg:grid-cols-[14rem_1fr]">
                <nav
                    aria-label="Account"
                    className="-mx-4 flex gap-1 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:px-0"
                >
                    {items.map(({ title, href, icon: Icon, exact }) => {
                        const active = exact
                            ? isCurrentUrl(href)
                            : isCurrentOrParentUrl(href);

                        return (
                            <Link
                                key={title}
                                href={href}
                                prefetch
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'flex shrink-0 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    active
                                        ? 'bg-primary/10 text-primary'
                                        : 'text-muted-foreground hover:bg-accent hover:text-foreground',
                                )}
                            >
                                <Icon className="size-4" />
                                {title}
                            </Link>
                        );
                    })}
                </nav>
                <div className="min-w-0">{children}</div>
            </div>
        </Container>
    );
}
