import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AdminLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {children}
            </div>
        </AppSidebarLayout>
    );
}
