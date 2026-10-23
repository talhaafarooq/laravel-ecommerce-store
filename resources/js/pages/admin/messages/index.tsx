import { Head, Link } from '@inertiajs/react';
import { Inbox } from 'lucide-react';
import { DataCard } from '@/components/admin/data-card';
import { PageHeader } from '@/components/admin/page-header';
import { SegmentedControl } from '@/components/admin/segmented-control';
import { EmptyState } from '@/components/shop/empty-state';
import { Pagination } from '@/components/shop/pagination';
import { useFilters } from '@/hooks/use-filters';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type { ContactMessage, Paginated } from '@/types';

export default function MessagesIndex({
    messages,
    filters,
}: {
    messages: Paginated<ContactMessage>;
    filters: { unread: boolean };
}) {
    const apply = useFilters(admin.messages.index.url(), filters);

    return (
        <>
            <Head title="Messages" />
            <PageHeader
                title="Messages"
                description="Enquiries sent through the contact form."
                actions={
                    <SegmentedControl
                        label="Filter messages"
                        value={filters.unread ? 'unread' : 'all'}
                        onChange={(value) =>
                            apply({ unread: value === 'unread' })
                        }
                        options={[
                            { value: 'all', label: 'All' },
                            { value: 'unread', label: 'Unread' },
                        ]}
                    />
                }
            />

            <DataCard flush>
                {messages.data.length === 0 ? (
                    <EmptyState
                        icon={Inbox}
                        title="Nothing here"
                        description="New messages will show up in this inbox."
                        className="m-6 border-0"
                    />
                ) : (
                    <ul className="divide-y">
                        {messages.data.map((message) => (
                            <li key={message.id}>
                                <Link
                                    href={admin.messages.show(message.id)}
                                    className="flex items-start gap-4 px-5 py-4 transition-colors hover:bg-accent/50"
                                >
                                    <span
                                        className={cn(
                                            'mt-2 size-2 shrink-0 rounded-full',
                                            message.is_read
                                                ? 'bg-transparent'
                                                : 'bg-primary',
                                        )}
                                        aria-hidden
                                    />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-baseline justify-between gap-x-4">
                                            <p
                                                className={cn(
                                                    'truncate',
                                                    !message.is_read &&
                                                        'font-semibold',
                                                )}
                                            >
                                                {message.name}{' '}
                                                <span className="font-normal text-muted-foreground">
                                                    &lt;{message.email}&gt;
                                                </span>
                                            </p>
                                            <time className="text-xs text-muted-foreground">
                                                {formatDateTime(
                                                    message.created_at,
                                                )}
                                            </time>
                                        </div>
                                        <p
                                            className={cn(
                                                'truncate text-sm',
                                                !message.is_read &&
                                                    'font-medium',
                                            )}
                                        >
                                            {message.subject}
                                            {!message.is_read && (
                                                <span className="sr-only">
                                                    {' '}
                                                    (unread)
                                                </span>
                                            )}
                                        </p>
                                        <p className="line-clamp-1 text-sm text-muted-foreground">
                                            {message.message}
                                        </p>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
                <div className="border-t p-4">
                    <Pagination meta={messages.meta} />
                </div>
            </DataCard>
        </>
    );
}

MessagesIndex.layout = withAdminLayout({
    title: 'Messages',
    href: admin.messages.index(),
});
