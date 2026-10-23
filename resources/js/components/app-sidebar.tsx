import { Link } from '@inertiajs/react';
import {
    BarChart3,
    Boxes,
    FileText,
    FolderTree,
    HelpCircle,
    LayoutGrid,
    Mail,
    MessageSquareText,
    Package,
    ShoppingCart,
    Store,
    Tag,
    TicketPercent,
    Users,
} from 'lucide-react';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { StoreLogo } from '@/components/shop/store-logo';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { home } from '@/routes';
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

const storeItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: admin.dashboard(),
        icon: LayoutGrid,
        exact: true,
    },
    { title: 'Orders', href: admin.orders.index(), icon: ShoppingCart },
    { title: 'Products', href: admin.products.index(), icon: Package },
    { title: 'Inventory', href: admin.inventory.index(), icon: Boxes },
    { title: 'Categories', href: admin.categories.index(), icon: FolderTree },
    { title: 'Brands', href: admin.brands.index(), icon: Tag },
    { title: 'Customers', href: admin.users.index(), icon: Users },
];

const growthItems: NavItem[] = [
    { title: 'Sales reports', href: admin.reports.index(), icon: BarChart3 },
    { title: 'Coupons', href: admin.coupons.index(), icon: TicketPercent },
    { title: 'Reviews', href: admin.reviews.index(), icon: MessageSquareText },
];

const contentItems: NavItem[] = [
    { title: 'Blog posts', href: admin.posts.index(), icon: FileText },
    { title: 'FAQs', href: admin.faqs.index(), icon: HelpCircle },
    { title: 'Messages', href: admin.messages.index(), icon: Mail },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={admin.dashboard()} prefetch>
                                <StoreLogo className="[&>span:last-child]:group-data-[collapsible=icon]:hidden" />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="gap-4">
                <NavMain items={storeItems} label="Store" />
                <NavMain items={growthItems} label="Growth" />
                <NavMain items={contentItems} label="Content" />
            </SidebarContent>

            <SidebarFooter>
                <SidebarGroup className="p-0">
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                asChild
                                tooltip={{ children: 'View store' }}
                            >
                                <Link href={home()}>
                                    <Store />
                                    <span>View store</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
