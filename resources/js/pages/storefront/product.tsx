import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import {
    Check,
    ChevronRight,
    RotateCcw,
    ShieldCheck,
    Trash2,
    Truck,
} from 'lucide-react';
import { useState } from 'react';
import { AddToCartButton } from '@/components/shop/add-to-cart-button';
import { Container } from '@/components/shop/container';
import { Pagination } from '@/components/shop/pagination';
import { Price } from '@/components/shop/price';
import {
    ProductGrid,
    ProductGridSkeleton,
} from '@/components/shop/product-card';
import { ProductImage } from '@/components/shop/product-image';
import { QuantityInput } from '@/components/shop/quantity-input';
import { RatingStars } from '@/components/shop/rating-stars';
import { ReviewForm } from '@/components/shop/review-form';
import { SectionHeading } from '@/components/shop/section-heading';
import { StatusBadge } from '@/components/shop/status-badge';
import { WishlistButton } from '@/components/shop/wishlist-button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useInitials } from '@/hooks/use-initials';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { home, login } from '@/routes';
import { show as categoryShow } from '@/routes/categories';
import { destroy as destroyReview } from '@/routes/reviews';
import { index as shopIndex } from '@/routes/shop';
import type { Paginated, Product, ProductCard, Review } from '@/types';

type Props = {
    product: Product;
    reviews: Paginated<Review>;
    ratingBreakdown: Record<string, number>;
    ownReview: Review | null;
    canReview: boolean;
    related?: ProductCard[];
    recentlyViewed: ProductCard[];
};

export default function ProductPage({
    product,
    reviews,
    ratingBreakdown,
    ownReview,
    canReview,
    related,
    recentlyViewed,
}: Props) {
    const { auth } = usePage().props;
    const [active, setActive] = useState(0);
    const [quantity, setQuantity] = useState(1);
    const images = product.images.length
        ? product.images
        : [
              {
                  id: 0,
                  url: product.image,
                  thumbnail: product.image,
                  alt: product.name,
                  position: 0,
              },
          ];
    const selected = images[Math.min(active, images.length - 1)];
    const totalRatings = Object.values(ratingBreakdown).reduce(
        (sum, value) => sum + value,
        0,
    );

    return (
        <>
            <Head title={product.meta_title}>
                <meta
                    name="description"
                    content={
                        product.meta_description ??
                        product.short_description ??
                        ''
                    }
                />
                <meta property="og:title" content={product.name} />
                <meta property="og:type" content="product" />
                {selected?.url && (
                    <meta property="og:image" content={selected.url} />
                )}
            </Head>

            <Container className="py-6">
                <nav
                    aria-label="Breadcrumb"
                    className="flex flex-wrap items-center gap-1 text-sm text-muted-foreground"
                >
                    <Link href={home()} className="hover:text-foreground">
                        Home
                    </Link>
                    <ChevronRight className="size-3.5" />
                    <Link href={shopIndex()} className="hover:text-foreground">
                        Shop
                    </Link>
                    {product.category.parent && (
                        <>
                            <ChevronRight className="size-3.5" />
                            <Link
                                href={categoryShow(
                                    product.category.parent.slug,
                                )}
                                className="hover:text-foreground"
                            >
                                {product.category.parent.name}
                            </Link>
                        </>
                    )}
                    <ChevronRight className="size-3.5" />
                    <Link
                        href={categoryShow(product.category.slug)}
                        className="hover:text-foreground"
                    >
                        {product.category.name}
                    </Link>
                </nav>
            </Container>

            <Container className="grid gap-10 pb-12 lg:grid-cols-2 lg:gap-14">
                <div className="space-y-4">
                    <ProductImage
                        src={selected?.url ?? null}
                        alt={selected?.alt ?? product.name}
                        loading="eager"
                        className="aspect-square rounded-2xl border p-6"
                    />
                    {images.length > 1 && (
                        <ul className="flex gap-3 overflow-x-auto">
                            {images.map((image, index) => (
                                <li key={image.id}>
                                    <button
                                        type="button"
                                        onClick={() => setActive(index)}
                                        aria-label={`Show image ${index + 1}`}
                                        aria-current={index === active}
                                        className={cn(
                                            'block overflow-hidden rounded-lg border-2 transition-colors',
                                            index === active
                                                ? 'border-primary'
                                                : 'border-transparent hover:border-border',
                                        )}
                                    >
                                        <ProductImage
                                            src={image.thumbnail}
                                            alt=""
                                            className="size-20 p-1.5"
                                        />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div>
                    {product.brand && (
                        <Link
                            href={shopIndex({
                                query: { brands: [product.brand.slug] },
                            })}
                            className="text-xs font-semibold tracking-widest text-primary uppercase hover:underline"
                        >
                            {product.brand.name}
                        </Link>
                    )}
                    <h1 className="mt-1 text-3xl font-semibold tracking-tight text-balance">
                        {product.name}
                    </h1>

                    <div className="mt-3 flex flex-wrap items-center gap-3">
                        <RatingStars rating={product.rating} size="md" />
                        <a
                            href="#reviews"
                            className="text-sm text-muted-foreground hover:text-foreground"
                        >
                            {product.reviews_count}{' '}
                            {product.reviews_count === 1 ? 'review' : 'reviews'}
                        </a>
                        <span className="text-sm text-muted-foreground">
                            SKU: {product.sku}
                        </span>
                    </div>

                    <div className="mt-6 flex items-center gap-3">
                        <Price
                            price={product.price}
                            compareAt={product.compare_at_price}
                            size="lg"
                        />
                        {product.discount_percentage && (
                            <span className="rounded-full bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white">
                                Save {product.discount_percentage}%
                            </span>
                        )}
                    </div>

                    {product.short_description && (
                        <p className="mt-5 text-muted-foreground">
                            {product.short_description}
                        </p>
                    )}

                    <div className="mt-6 flex items-center gap-2">
                        <StatusBadge tone={product.stock_status.tone}>
                            {product.stock_status.value === 'low_stock'
                                ? `Only ${product.stock_quantity} left`
                                : product.stock_status.label}
                        </StatusBadge>
                    </div>

                    <div className="mt-6 flex flex-wrap items-center gap-3">
                        {product.in_stock && (
                            <QuantityInput
                                value={quantity}
                                max={product.max_quantity}
                                onChange={setQuantity}
                            />
                        )}
                        <AddToCartButton
                            productId={product.id}
                            quantity={quantity}
                            disabled={!product.in_stock}
                            size="lg"
                            className="min-w-48 flex-1 sm:flex-none"
                        />
                        <WishlistButton
                            productId={product.id}
                            slug={product.slug}
                            withLabel
                            className="h-10"
                        />
                    </div>

                    <ul className="mt-8 grid gap-3 rounded-xl border bg-muted/30 p-4 text-sm sm:grid-cols-3">
                        {[
                            { icon: Truck, text: 'Fast, tracked delivery' },
                            { icon: RotateCcw, text: '30-day easy returns' },
                            { icon: ShieldCheck, text: 'Secure payment' },
                        ].map(({ icon: Icon, text }) => (
                            <li key={text} className="flex items-center gap-2">
                                <Icon className="size-4 text-primary" />
                                {text}
                            </li>
                        ))}
                    </ul>
                </div>
            </Container>

            <Container className="pb-12">
                <Tabs defaultValue="description">
                    <TabsList>
                        <TabsTrigger value="description">
                            Description
                        </TabsTrigger>
                        <TabsTrigger value="reviews">
                            Reviews ({product.reviews_count})
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent value="description" className="pt-6">
                        <div
                            className="rich-text max-w-3xl"
                            // Server-rendered, sanitised Markdown (raw HTML is stripped).
                            dangerouslySetInnerHTML={{
                                __html: product.description_html,
                            }}
                        />
                    </TabsContent>
                    <TabsContent value="reviews" className="pt-6" id="reviews">
                        <div className="grid gap-10 lg:grid-cols-[18rem_1fr]">
                            <div className="space-y-6">
                                <div>
                                    <p className="text-5xl font-semibold tracking-tight">
                                        {product.rating.toFixed(1)}
                                    </p>
                                    <RatingStars
                                        rating={product.rating}
                                        size="md"
                                        className="mt-2"
                                    />
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Based on {product.reviews_count} reviews
                                    </p>
                                </div>
                                <ul className="space-y-2">
                                    {[5, 4, 3, 2, 1].map((star) => (
                                        <li
                                            key={star}
                                            className="flex items-center gap-3 text-sm"
                                        >
                                            <span className="w-8 shrink-0 text-muted-foreground">
                                                {star}★
                                            </span>
                                            <Progress
                                                value={
                                                    totalRatings
                                                        ? ((ratingBreakdown[
                                                              star
                                                          ] ?? 0) /
                                                              totalRatings) *
                                                          100
                                                        : 0
                                                }
                                                className="h-2"
                                            />
                                            <span className="w-8 shrink-0 text-right text-muted-foreground">
                                                {ratingBreakdown[star] ?? 0}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                                {canReview ? (
                                    <ReviewForm
                                        slug={product.slug}
                                        existing={ownReview}
                                    />
                                ) : (
                                    <p className="rounded-xl border bg-muted/30 p-4 text-sm">
                                        <Link
                                            href={login()}
                                            className="font-medium text-primary hover:underline"
                                        >
                                            Sign in
                                        </Link>{' '}
                                        to write a review.
                                    </p>
                                )}
                            </div>

                            <div>
                                {reviews.data.length === 0 ? (
                                    <p className="rounded-xl border border-dashed p-8 text-center text-muted-foreground">
                                        No reviews yet. Be the first to share
                                        your thoughts!
                                    </p>
                                ) : (
                                    <ul className="divide-y">
                                        {reviews.data.map((review) => (
                                            <ReviewItem
                                                key={review.id}
                                                review={review}
                                                isOwn={
                                                    auth.user?.id ===
                                                    review.author.id
                                                }
                                            />
                                        ))}
                                    </ul>
                                )}
                                <Pagination
                                    meta={reviews.meta}
                                    only={['reviews']}
                                    className="mt-6"
                                />
                            </div>
                        </div>
                    </TabsContent>
                </Tabs>
            </Container>

            <Container className="pb-8">
                <SectionHeading title="You may also like" />
                <Deferred data="related" fallback={<ProductGridSkeleton />}>
                    <ProductGrid products={related ?? []} />
                </Deferred>
            </Container>

            {recentlyViewed.length > 0 && (
                <Container className="pb-8">
                    <SectionHeading title="Recently viewed" />
                    <ProductGrid products={recentlyViewed} />
                </Container>
            )}
        </>
    );
}

function ReviewItem({ review, isOwn }: { review: Review; isOwn: boolean }) {
    const getInitials = useInitials();

    return (
        <li className="flex gap-4 py-6 first:pt-0">
            <Avatar className="size-10">
                <AvatarImage src={review.author.avatar ?? undefined} alt="" />
                <AvatarFallback className="bg-primary/10 text-primary">
                    {getInitials(review.author.name)}
                </AvatarFallback>
            </Avatar>
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <p className="font-medium">{review.author.name}</p>
                    {review.is_verified_purchase && (
                        <span className="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                            <Check className="size-3.5" />
                            Verified purchase
                        </span>
                    )}
                    <time
                        className="text-xs text-muted-foreground"
                        dateTime={review.created_at ?? undefined}
                    >
                        {formatDate(review.created_at)}
                    </time>
                </div>
                <RatingStars rating={review.rating} className="mt-1" />
                {review.title && (
                    <p className="mt-2 font-medium">{review.title}</p>
                )}
                <p className="mt-1 text-sm whitespace-pre-line text-muted-foreground">
                    {review.body}
                </p>
                {review.can_delete && (
                    <button
                        type="button"
                        onClick={() =>
                            router.delete(destroyReview(review.id), {
                                preserveScroll: true,
                            })
                        }
                        className="mt-3 inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-destructive"
                    >
                        <Trash2 className="size-3.5" />
                        {isOwn ? 'Delete my review' : 'Delete'}
                    </button>
                )}
            </div>
        </li>
    );
}
