import { Link } from '@inertiajs/react';
import { AddToCartButton } from '@/components/shop/add-to-cart-button';
import { Price } from '@/components/shop/price';
import { ProductImage } from '@/components/shop/product-image';
import { RatingStars } from '@/components/shop/rating-stars';
import { WishlistButton } from '@/components/shop/wishlist-button';
import { cn } from '@/lib/utils';
import { show } from '@/routes/products';
import type { ProductCard as ProductCardData } from '@/types';

export function ProductCard({
    product,
    className,
}: {
    product: ProductCardData;
    className?: string;
}) {
    return (
        <article
            className={cn(
                'group relative flex flex-col overflow-hidden rounded-xl border bg-card transition-shadow hover:shadow-lg hover:shadow-black/5',
                className,
            )}
        >
            <Link
                href={show(product.slug)}
                prefetch
                className="relative block"
                aria-label={product.name}
            >
                <ProductImage
                    src={product.image}
                    alt={product.name}
                    className="aspect-square p-4"
                    imgClassName="transition-transform duration-300 group-hover:scale-105"
                />
                <div className="absolute top-3 left-3 flex flex-col gap-1.5">
                    {product.is_on_sale && product.discount_percentage && (
                        <span className="rounded-full bg-rose-600 px-2 py-0.5 text-xs font-semibold text-white">
                            -{product.discount_percentage}%
                        </span>
                    )}
                    {!product.in_stock && (
                        <span className="rounded-full bg-foreground/80 px-2 py-0.5 text-xs font-medium text-background">
                            Sold out
                        </span>
                    )}
                </div>
            </Link>

            <WishlistButton
                productId={product.id}
                slug={product.slug}
                className="absolute top-3 right-3"
            />

            <div className="flex flex-1 flex-col gap-2 p-4">
                {(product.brand || product.category) && (
                    <p className="truncate text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        {product.brand?.name ?? product.category?.name}
                    </p>
                )}
                <h3 className="line-clamp-2 text-sm leading-snug font-medium">
                    <Link
                        href={show(product.slug)}
                        className="hover:text-primary"
                    >
                        {product.name}
                    </Link>
                </h3>
                {product.reviews_count > 0 && (
                    <RatingStars
                        rating={product.rating}
                        count={product.reviews_count}
                    />
                )}
                <div className="mt-auto flex items-end justify-between gap-2 pt-2">
                    <Price
                        price={product.price}
                        compareAt={product.compare_at_price}
                    />
                </div>
                <AddToCartButton
                    productId={product.id}
                    disabled={!product.in_stock}
                    size="sm"
                    className="mt-1 w-full"
                />
            </div>
        </article>
    );
}

export function ProductGrid({
    products,
    className,
}: {
    products: ProductCardData[];
    className?: string;
}) {
    return (
        <div
            className={cn(
                'grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 xl:grid-cols-4',
                className,
            )}
        >
            {products.map((product) => (
                <ProductCard key={product.id} product={product} />
            ))}
        </div>
    );
}

export function ProductGridSkeleton({ count = 4 }: { count?: number }) {
    return (
        <div className="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
            {Array.from({ length: count }, (_, index) => (
                <div
                    key={index}
                    className="animate-pulse overflow-hidden rounded-xl border"
                >
                    <div className="aspect-square bg-muted" />
                    <div className="space-y-2 p-4">
                        <div className="h-3 w-1/3 rounded bg-muted" />
                        <div className="h-4 w-4/5 rounded bg-muted" />
                        <div className="h-4 w-1/4 rounded bg-muted" />
                    </div>
                </div>
            ))}
        </div>
    );
}
