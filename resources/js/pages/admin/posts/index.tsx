import { Head, Link, router } from '@inertiajs/react';
import { FileText, Pencil, Plus, Trash2 } from 'lucide-react';
import { ConfirmButton } from '@/components/admin/confirm-button';
import { DataCard } from '@/components/admin/data-card';
import { PageHeader } from '@/components/admin/page-header';
import { SearchInput } from '@/components/admin/search-input';
import { EmptyState } from '@/components/shop/empty-state';
import { Pagination } from '@/components/shop/pagination';
import { StatusBadge } from '@/components/shop/status-badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import { formatDate } from '@/lib/format';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import { show as blogShow } from '@/routes/blog';
import type { Paginated, Post } from '@/types';

export function postStatus(post: Post): {
    label: string;
    tone: 'success' | 'info' | 'neutral';
} {
    if (!post.published_at) {
        return { label: 'Draft', tone: 'neutral' };
    }

    return post.is_published
        ? { label: 'Published', tone: 'success' }
        : { label: 'Scheduled', tone: 'info' };
}

export default function PostsIndex({
    posts,
    filters,
}: {
    posts: Paginated<Post>;
    filters: { search: string | null };
}) {
    const apply = useFilters(admin.posts.index.url(), filters);

    return (
        <>
            <Head title="Blog posts" />
            <PageHeader
                title="Blog posts"
                description="Guides and announcements that live on your storefront blog."
                actions={
                    <Button asChild>
                        <Link href={admin.posts.create()}>
                            <Plus />
                            New post
                        </Link>
                    </Button>
                }
            />

            <DataCard flush>
                <div className="border-b p-4">
                    <SearchInput
                        value={filters.search}
                        onSearch={(search) => apply({ search })}
                        placeholder="Search titles…"
                        className="w-full sm:w-72"
                    />
                </div>

                {posts.data.length === 0 ? (
                    <EmptyState
                        icon={FileText}
                        title="No posts found"
                        className="m-6 border-0"
                    />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Post</TableHead>
                                <TableHead>Author</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Publish date</TableHead>
                                <TableHead className="text-right">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {posts.data.map((post) => {
                                const status = postStatus(post);

                                return (
                                    <TableRow key={post.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <span className="h-11 w-16 shrink-0 overflow-hidden rounded-md border bg-muted">
                                                    {post.cover && (
                                                        <img
                                                            src={post.cover}
                                                            alt=""
                                                            className="size-full object-cover"
                                                        />
                                                    )}
                                                </span>
                                                <div className="min-w-0">
                                                    <Link
                                                        href={admin.posts.edit(
                                                            post.id,
                                                        )}
                                                        className="block max-w-80 truncate font-medium hover:text-primary"
                                                    >
                                                        {post.title}
                                                    </Link>
                                                    <p className="text-xs text-muted-foreground">
                                                        {post.reading_time} min
                                                        read
                                                    </p>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {post.author?.name}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge tone={status.tone}>
                                                {status.label}
                                            </StatusBadge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {formatDate(post.published_at)}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-1">
                                                {post.is_published && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <a
                                                            href={blogShow.url(
                                                                post.slug,
                                                            )}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                        >
                                                            View
                                                        </a>
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={admin.posts.edit(
                                                            post.id,
                                                        )}
                                                        aria-label={`Edit ${post.title}`}
                                                    >
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                                <ConfirmButton
                                                    title={`Delete “${post.title}”?`}
                                                    description="The post and its cover image are removed permanently."
                                                    onConfirm={() =>
                                                        router.delete(
                                                            admin.posts.destroy(
                                                                post.id,
                                                            ),
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Delete ${post.title}`}
                                                        className="text-destructive"
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </ConfirmButton>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                )}
                <div className="border-t p-4">
                    <Pagination meta={posts.meta} />
                </div>
            </DataCard>
        </>
    );
}

PostsIndex.layout = withAdminLayout({
    title: 'Blog posts',
    href: admin.posts.index(),
});
