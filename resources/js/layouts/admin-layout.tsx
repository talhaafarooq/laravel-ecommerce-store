import type { ReactNode } from 'react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import admin from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';

function AdminLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
}) {
    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {children}
            </div>
        </AppSidebarLayout>
    );
}

/**
 * Page-level layout for the admin area.
 *
 * Admin pages opt in with `Page.layout = withAdminLayout(...)` instead of being
 * matched in app.tsx, so the sidebar and the admin route tree are only ever
 * downloaded together with an admin page, never for storefront visitors.
 */
export function withAdminLayout(...trail: BreadcrumbItem[]) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: admin.dashboard() },
        ...trail,
    ];

    return (page: ReactNode) => (
        <AdminLayout
            breadcrumbs={trail.length === 0 ? [breadcrumbs[0]] : breadcrumbs}
        >
            {page}
        </AdminLayout>
    );
}
