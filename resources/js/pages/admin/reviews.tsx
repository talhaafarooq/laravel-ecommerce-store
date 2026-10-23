import { Head, Link, router } from '@inertiajs/react';
import { Check, EyeOff, MessageSquareText, Trash2 } from 'lucide-react';
import { ConfirmButton } from '@/components/admin/confirm-button';
import { DataCard } from '@/components/admin/data-card';
import { FilterSelect } from '@/components/admin/filter-select';
import { PageHeader } from '@/components/admin/page-header';
import { SearchInput } from '@/components/admin/search-input';
import { EmptyState } from '@/components/shop/empty-state';
import { Pagination } from '@/components/shop/pagination';
import { RatingStars } from '@/components/shop/rating-stars';
import { StatusBadge } from '@/components/shop/status-badge';
import { Button } from '@/components/ui/button';
import { useFilters } from '@/hooks/use-filters';
import { formatDate } from '@/lib/format';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import { show as productShow } from '@/routes/products';
import type { Paginated, Review } from '@/types';

type Props = {
    reviews: Paginated<Review>;
    filters: { search: string | null; status: string | null };
};

export default function Reviews({ reviews, filters }: Props) {
    const apply = useFilters(admin.reviews.index.url(), filters);

    const setApproval = (review: Review, approved: boolean) =>
        router.patch(
            admin.reviews.update(review.id),
            { is_approved: approved },
            { preserveScroll: true },
        );

    return (
        <>
            <Head title="Reviews" />
            <PageHeader
                title="Reviews"
                description="Moderate what customers say about your products."
            />

            <DataCard flush>
                <div className="flex flex-wrap items-center gap-3 border-b p-4">
                    <SearchInput
                        value={filters.search}
                        onSearch={(search) => apply({ search })}
                        placeholder="Search reviews or products…"
                        className="w-full sm:w-72"
                    />
                    <FilterSelect
                        label="Status"
                        allLabel="All reviews"
                        value={filters.status}
                        onChange={(status) => apply({ status })}
                        options={[
                            { value: 'approved', label: 'Published' },
                            { value: 'pending', label: 'Awaiting approval' },
                        ]}
                        className="w-48"
                    />
                </div>

                {reviews.data.length === 0 ? (
                    <EmptyState
                        icon={MessageSquareText}
                        title="No reviews found"
                        className="m-6 border-0"
                    />
                ) : (
                    <ul className="divide-y">
                        {reviews.data.map((review) => (
                            <li
                                key={review.id}
                                className="flex flex-col gap-4 p-5 sm:flex-row sm:items-start"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <RatingStars rating={review.rating} />
                                        <StatusBadge
                                            tone={
                                                review.is_approved
                                                    ? 'success'
                                                    : 'warning'
                                            }
                                        >
                                            {review.is_approved
                                                ? 'Published'
                                                : 'Awaiting approval'}
                                        </StatusBadge>
                                        {review.is_verified_purchase && (
                                            <span className="text-xs text-muted-foreground">
                                                Verified purchase
                                            </span>
                                        )}
                                    </div>
                                    {review.title && (
                                        <p className="mt-2 font-medium">
                                            {review.title}
                                        </p>
                                    )}
                                    <p className="mt-1 text-sm whitespace-pre-line text-muted-foreground">
                                        {review.body}
                                    </p>
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        {review.author.name} ·{' '}
                                        {formatDate(review.created_at)} · on{' '}
                                        {review.product &&
                                        !review.product.trashed ? (
                                            <Link
                                                href={productShow(
                                                    review.product.slug,
                                                )}
                                                className="hover:underline"
                                            >
                                                {review.product.name}
                                            </Link>
                                        ) : (
                                            (review.product?.name ??
                                            'a deleted product')
                                        )}
                                    </p>
                                </div>
                                <div className="flex shrink-0 gap-2">
                                    {review.is_approved ? (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                setApproval(review, false)
                                            }
                                        >
                                            <EyeOff />
                                            Hide
                                        </Button>
                                    ) : (
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                setApproval(review, true)
                                            }
                                        >
                                            <Check />
                                            Approve
                                        </Button>
                                    )}
                                    <ConfirmButton
                                        title="Delete this review?"
                                        description="The review is removed permanently and the product rating is recalculated."
                                        onConfirm={() =>
                                            router.delete(
                                                admin.reviews.destroy(
                                                    review.id,
                                                ),
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Delete review"
                                            className="text-destructive"
                                        >
                                            <Trash2 />
                                        </Button>
                                    </ConfirmButton>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
                <div className="border-t p-4">
                    <Pagination meta={reviews.meta} />
                </div>
            </DataCard>
        </>
    );
}

Reviews.layout = withAdminLayout({
    title: 'Reviews',
    href: admin.reviews.index(),
});
