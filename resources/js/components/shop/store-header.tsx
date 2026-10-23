import { Link, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    Heart,
    LayoutDashboard,
    LogIn,
    Menu,
    Package,
    ShoppingCart,
    Truck,
    UserRound,
} from 'lucide-react';
import { useState } from 'react';
import { SearchBox } from '@/components/shop/search-box';
import { StoreLogo } from '@/components/shop/store-logo';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuList,
    NavigationMenuTrigger,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { UserMenuContent } from '@/components/user-menu-content';
import { Container } from '@/components/shop/container';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import {
    about,
    contact,
    dashboard,
    faq,
    home,
    login,
    register,
} from '@/routes';
import { dashboard as accountDashboard } from '@/routes/account';
import { index as ordersIndex } from '@/routes/account/orders';
import { index as wishlistIndex } from '@/routes/account/wishlist';
import { index as blogIndex } from '@/routes/blog';
import { index as cartIndex } from '@/routes/cart';
import { show as categoryShow } from '@/routes/categories';
import { index as shopIndex } from '@/routes/shop';

const pages = [
    { title: 'Shop all', href: shopIndex.url() },
    { title: 'Deals', href: shopIndex.url({ query: { on_sale: 1 } }) },
    { title: 'Blog', href: blogIndex.url() },
    { title: 'About', href: about.url() },
    { title: 'Contact', href: contact.url() },
];

export function StoreHeader() {
    const { auth, navigation, cartCount, wishlistIds, store } = usePage().props;
    const getInitials = useInitials();
    const [mobileOpen, setMobileOpen] = useState(false);
    const user = auth.user;

    return (
        <header className="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
            <div className="hidden bg-foreground text-background sm:block">
                <Container className="flex h-9 items-center justify-between text-xs">
                    <p className="flex items-center gap-2">
                        <Truck className="size-3.5" />
                        {store.free_shipping_threshold
                            ? `Free shipping on orders over ${store.free_shipping_threshold.formatted}`
                            : 'Fast, tracked shipping on every order'}
                    </p>
                    <div className="flex items-center gap-5 opacity-80">
                        <Link href={faq()} className="hover:opacity-100">
                            Help &amp; FAQ
                        </Link>
                        <a
                            href={`mailto:${store.support_email}`}
                            className="hover:opacity-100"
                        >
                            {store.support_email}
                        </a>
                    </div>
                </Container>
            </div>

            <Container className="flex h-16 items-center gap-4">
                <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                    <SheetTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="lg:hidden"
                            aria-label="Open menu"
                        >
                            <Menu className="size-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" className="w-80 overflow-y-auto">
                        <SheetHeader>
                            <SheetTitle>
                                <StoreLogo />
                            </SheetTitle>
                        </SheetHeader>
                        <div className="px-4">
                            <SearchBox
                                onNavigate={() => setMobileOpen(false)}
                            />
                        </div>
                        <nav className="space-y-6 px-4 pb-8">
                            <ul className="space-y-1">
                                {pages.map((page) => (
                                    <li key={page.title}>
                                        <Link
                                            href={page.href}
                                            onClick={() => setMobileOpen(false)}
                                            className="block rounded-md px-2 py-2 text-sm font-medium hover:bg-accent"
                                        >
                                            {page.title}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                            <div>
                                <p className="mb-2 px-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Departments
                                </p>
                                <ul className="space-y-1">
                                    {navigation.map((category) => (
                                        <li key={category.id}>
                                            <Link
                                                href={categoryShow(
                                                    category.slug,
                                                )}
                                                onClick={() =>
                                                    setMobileOpen(false)
                                                }
                                                className="block rounded-md px-2 py-2 text-sm hover:bg-accent"
                                            >
                                                {category.name}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </nav>
                    </SheetContent>
                </Sheet>

                <Link href={home()} className="shrink-0" aria-label="Home">
                    <StoreLogo />
                </Link>

                <SearchBox className="mx-4 hidden max-w-xl flex-1 md:block" />

                <div className="ml-auto flex items-center gap-1">
                    {user && (
                        <Button
                            variant="ghost"
                            size="icon"
                            asChild
                            className="relative hidden sm:inline-flex"
                        >
                            <Link href={wishlistIndex()} aria-label="Wishlist">
                                <Heart className="size-5" />
                                {wishlistIds.length > 0 && (
                                    <CountBadge count={wishlistIds.length} />
                                )}
                            </Link>
                        </Button>
                    )}

                    <Button
                        variant="ghost"
                        size="icon"
                        asChild
                        className="relative"
                    >
                        <Link href={cartIndex()} aria-label="Shopping cart">
                            <ShoppingCart className="size-5" />
                            {cartCount > 0 && <CountBadge count={cartCount} />}
                        </Link>
                    </Button>

                    {user ? (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="ml-1 gap-2 rounded-full px-1.5"
                                    aria-label="Account menu"
                                >
                                    <Avatar className="size-8">
                                        <AvatarImage
                                            src={user.avatar ?? undefined}
                                            alt={user.name}
                                        />
                                        <AvatarFallback className="bg-primary/10 text-xs text-primary">
                                            {getInitials(user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <ChevronDown className="hidden size-4 text-muted-foreground sm:block" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-60" align="end">
                                <UserMenuContent
                                    user={user}
                                    links={[
                                        ...(user.is_admin
                                            ? [
                                                  {
                                                      title: 'Admin dashboard',
                                                      href: dashboard.url(),
                                                      icon: LayoutDashboard,
                                                  },
                                              ]
                                            : []),
                                        {
                                            title: 'My account',
                                            href: accountDashboard.url(),
                                            icon: UserRound,
                                        },
                                        {
                                            title: 'My orders',
                                            href: ordersIndex.url(),
                                            icon: Package,
                                        },
                                        {
                                            title: 'Wishlist',
                                            href: wishlistIndex.url(),
                                            icon: Heart,
                                        },
                                    ]}
                                />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    ) : (
                        <div className="ml-1 flex items-center gap-1">
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={login()}>
                                    <LogIn className="sm:hidden" />
                                    <span className="hidden sm:inline">
                                        Sign in
                                    </span>
                                </Link>
                            </Button>
                            <Button
                                size="sm"
                                asChild
                                className="hidden sm:inline-flex"
                            >
                                <Link href={register()}>Create account</Link>
                            </Button>
                        </div>
                    )}
                </div>
            </Container>

            <Container className="pb-3 md:hidden">
                <SearchBox />
            </Container>

            <div className="hidden border-t lg:block">
                <Container className="flex h-11 items-center justify-between">
                    <NavigationMenu viewport={false}>
                        <NavigationMenuList className="gap-0">
                            {navigation.map((category) => (
                                <NavigationMenuItem key={category.id}>
                                    <NavigationMenuTrigger className="h-9 bg-transparent px-3 text-sm font-medium">
                                        <Link
                                            href={categoryShow(category.slug)}
                                        >
                                            {category.name}
                                        </Link>
                                    </NavigationMenuTrigger>
                                    {category.children &&
                                        category.children.length > 0 && (
                                            <NavigationMenuContent>
                                                <div className="grid w-[30rem] grid-cols-[1fr_11rem] gap-4 p-4">
                                                    <ul className="grid grid-cols-2 gap-1">
                                                        {category.children.map(
                                                            (child) => (
                                                                <li
                                                                    key={
                                                                        child.id
                                                                    }
                                                                >
                                                                    <Link
                                                                        href={categoryShow(
                                                                            child.slug,
                                                                        )}
                                                                        className="block rounded-md px-3 py-2 text-sm hover:bg-accent"
                                                                    >
                                                                        {
                                                                            child.name
                                                                        }
                                                                    </Link>
                                                                </li>
                                                            ),
                                                        )}
                                                    </ul>
                                                    <Link
                                                        href={categoryShow(
                                                            category.slug,
                                                        )}
                                                        className="group relative flex flex-col justify-end overflow-hidden rounded-lg bg-muted p-3"
                                                    >
                                                        {category.image && (
                                                            <img
                                                                src={
                                                                    category.image
                                                                }
                                                                alt=""
                                                                className="absolute inset-0 size-full object-cover opacity-90 transition-transform group-hover:scale-105"
                                                            />
                                                        )}
                                                        <span className="relative rounded bg-background/90 px-2 py-1 text-xs font-semibold">
                                                            Shop all{' '}
                                                            {category.name}
                                                        </span>
                                                    </Link>
                                                </div>
                                            </NavigationMenuContent>
                                        )}
                                </NavigationMenuItem>
                            ))}
                        </NavigationMenuList>
                    </NavigationMenu>
                    <nav className="flex items-center gap-5 text-sm">
                        {pages.slice(1, 3).map((page) => (
                            <Link
                                key={page.title}
                                href={page.href}
                                className={cn(
                                    'font-medium text-muted-foreground hover:text-foreground',
                                    page.title === 'Deals' &&
                                        'text-rose-600 hover:text-rose-700 dark:text-rose-400',
                                )}
                            >
                                {page.title}
                            </Link>
                        ))}
                    </nav>
                </Container>
            </div>
        </header>
    );
}

function CountBadge({ count }: { count: number }) {
    return (
        <span className="absolute -top-0.5 -right-0.5 flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground">
            {count > 99 ? '99+' : count}
        </span>
    );
}
