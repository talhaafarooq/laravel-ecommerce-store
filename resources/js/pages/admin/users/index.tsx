import { Head, Link } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { DataCard } from '@/components/admin/data-card';
import { FilterSelect } from '@/components/admin/filter-select';
import { PageHeader } from '@/components/admin/page-header';
import { SearchInput } from '@/components/admin/search-input';
import { EmptyState } from '@/components/shop/empty-state';
import { Pagination } from '@/components/shop/pagination';
import { StatusBadge } from '@/components/shop/status-badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import { useInitials } from '@/hooks/use-initials';
import { formatDate } from '@/lib/format';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type { Option, Paginated, User } from '@/types';

type Props = {
    users: Paginated<User>;
    filters: {
        search: string | null;
        role: string | null;
        status: string | null;
    };
    roles: Option[];
};

export default function UsersIndex({ users, filters, roles }: Props) {
    const apply = useFilters(admin.users.index.url(), filters);
    const getInitials = useInitials();

    return (
        <>
            <Head title="Customers" />
            <PageHeader
                title="Customers"
                description="Everyone with an account, including administrators."
            />

            <DataCard flush>
                <div className="flex flex-wrap items-center gap-3 border-b p-4">
                    <SearchInput
                        value={filters.search}
                        onSearch={(search) => apply({ search })}
                        placeholder="Search name, email or phone…"
                        className="w-full sm:w-72"
                    />
                    <FilterSelect
                        label="Role"
                        allLabel="All roles"
                        value={filters.role}
                        onChange={(role) => apply({ role })}
                        options={roles}
                        className="w-40"
                    />
                    <FilterSelect
                        label="Status"
                        allLabel="Any status"
                        value={filters.status}
                        onChange={(status) => apply({ status })}
                        options={[
                            { value: 'active', label: 'Active' },
                            { value: 'inactive', label: 'Deactivated' },
                        ]}
                        className="w-40"
                    />
                </div>

                {users.data.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        title="No accounts match"
                        className="m-6 border-0"
                    />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>User</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Orders</TableHead>
                                <TableHead>Spent</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Last login</TableHead>
                                <TableHead>Joined</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-3">
                                            <Avatar className="size-9">
                                                <AvatarImage
                                                    src={
                                                        user.avatar ?? undefined
                                                    }
                                                    alt=""
                                                />
                                                <AvatarFallback className="bg-primary/10 text-xs text-primary">
                                                    {getInitials(user.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0">
                                                <Link
                                                    href={admin.users.show(
                                                        user.id,
                                                    )}
                                                    className="block truncate font-medium hover:text-primary"
                                                >
                                                    {user.name}
                                                </Link>
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {user.email}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge
                                            tone={
                                                user.is_admin
                                                    ? 'primary'
                                                    : 'neutral'
                                            }
                                        >
                                            {user.role.label}
                                        </StatusBadge>
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {user.orders_count ?? 0}
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {user.total_spent?.formatted ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge
                                            tone={
                                                user.is_active
                                                    ? 'success'
                                                    : 'danger'
                                            }
                                        >
                                            {user.is_active
                                                ? 'Active'
                                                : 'Deactivated'}
                                        </StatusBadge>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {formatDate(user.last_login_at)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {formatDate(user.created_at)}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
                <div className="border-t p-4">
                    <Pagination meta={users.meta} />
                </div>
            </DataCard>
        </>
    );
}

UsersIndex.layout = withAdminLayout({
    title: 'Customers',
    href: admin.users.index(),
});
