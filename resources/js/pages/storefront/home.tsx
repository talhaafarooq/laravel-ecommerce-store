import { Deferred, Head, Link } from '@inertiajs/react';
import { ArrowRight, BadgePercent, Sparkles } from 'lucide-react';
import { PostCard } from '@/components/shop/post-card';
import {
    ProductGrid,
    ProductGridSkeleton,
} from '@/components/shop/product-card';
import { Container } from '@/components/shop/container';
import { ProductImage } from '@/components/shop/product-image';
import { SectionHeading } from '@/components/shop/section-heading';
import { Button } from '@/components/ui/button';
import { index as blogIndex } from '@/routes/blog';
import { show as categoryShow } from '@/routes/categories';
import { show as productShow } from '@/routes/products';
import { index as shopIndex } from '@/routes/shop';
import type { CategorySummary, Post, ProductCard } from '@/types';

type Props = {
    featured: ProductCard[];
    newArrivals: ProductCard[];
    bestSellers: ProductCard[];
    deals: ProductCard[];
    categories: CategorySummary[];
    posts?: Post[];
};

export default function Home({
    featured,
    newArrivals,
    bestSellers,
    deals,
    categories,
    posts,
}: Props) {
    const heroProducts = featured
        .filter((product) => product.image)
        .slice(0, 3);

    return (
        <>
            <Head title="Quality products, delivered fast">
                <meta
                    name="description"
                    content="Shop electronics, fashion, beauty, home essentials and more with fast delivery, secure checkout and 30-day returns."
                />
            </Head>

            <section className="relative overflow-hidden border-b bg-gradient-to-br from-primary/10 via-background to-background">
                <div className="pointer-events-none absolute -top-24 -right-24 size-96 rounded-full bg-primary/20 blur-3xl" />
                <Container className="relative grid items-center gap-12 py-16 lg:grid-cols-2 lg:py-24">
                    <div>
                        <span className="inline-flex items-center gap-1.5 rounded-full border bg-background px-3 py-1 text-xs font-medium">
                            <Sparkles className="size-3.5 text-primary" />
                            New season, new arrivals
                        </span>
                        <h1 className="mt-6 text-4xl font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                            Everything you love,{' '}
                            <span className="text-primary">in one place.</span>
                        </h1>
                        <p className="mt-5 max-w-lg text-lg text-muted-foreground">
                            Discover thousands of products from trusted brands —
                            with fast delivery, secure checkout and hassle-free
                            returns.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Button size="lg" asChild>
                                <Link href={shopIndex()}>
                                    Start shopping
                                    <ArrowRight />
                                </Link>
                            </Button>
                            <Button size="lg" variant="outline" asChild>
                                <Link
                                    href={shopIndex({ query: { on_sale: 1 } })}
                                >
                                    <BadgePercent />
                                    Browse deals
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {heroProducts.length > 0 && (
                        <div className="relative hidden h-[26rem] lg:block">
                            {heroProducts.map((product, index) => (
                                <Link
                                    key={product.id}
                                    href={productShow(product.slug)}
                                    className={[
                                        'absolute overflow-hidden rounded-2xl border bg-card shadow-xl transition-transform hover:-translate-y-1',
                                        index === 0 && 'top-0 left-8 z-10 w-64',
                                        index === 1 && 'top-16 right-0 w-56',
                                        index === 2 &&
                                            'bottom-0 left-36 z-20 w-52',
                                    ]
                                        .filter(Boolean)
                                        .join(' ')}
                                >
                                    <ProductImage
                                        src={product.image}
                                        alt={product.name}
                                        loading="eager"
                                        className="aspect-square bg-white p-4 dark:bg-muted"
                                    />
                                    <div className="border-t p-3">
                                        <p className="truncate text-sm font-medium">
                                            {product.name}
                                        </p>
                                        <p className="text-sm text-primary">
                                            {product.price.formatted}
                                        </p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </Container>
            </section>

            {categories.length > 0 && (
                <Container className="py-16">
                    <SectionHeading
                        eyebrow="Departments"
                        title="Shop by category"
                        href={shopIndex()}
                        linkLabel="All products"
                    />
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-8">
                        {categories.map((category) => (
                            <Link
                                key={category.id}
                                href={categoryShow(category.slug)}
                                className="group flex flex-col items-center gap-3 text-center"
                            >
                                <span className="relative block aspect-square w-full overflow-hidden rounded-2xl border bg-muted">
                                    {category.image && (
                                        <img
                                            src={category.image}
                                            alt=""
                                            loading="lazy"
                                            className="size-full object-cover transition-transform duration-300 group-hover:scale-110"
                                        />
                                    )}
                                </span>
                                <span className="text-sm font-medium group-hover:text-primary">
                                    {category.name}
                                </span>
                            </Link>
                        ))}
                    </div>
                </Container>
            )}

            {featured.length > 0 && (
                <Container className="pb-16">
                    <SectionHeading
                        eyebrow="Hand-picked"
                        title="Featured products"
                        href={shopIndex({ query: { sort: 'rating' } })}
                    />
                    <ProductGrid products={featured} />
                </Container>
            )}

            {deals.length > 0 && (
                <section className="bg-foreground py-16 text-background">
                    <Container className="grid items-center gap-10 lg:grid-cols-[1fr_2fr]">
                        <div>
                            <p className="text-xs font-semibold tracking-widest text-rose-400 uppercase">
                                Limited time
                            </p>
                            <h2 className="mt-2 text-3xl font-semibold tracking-tight">
                                Deals you don’t want to miss
                            </h2>
                            <p className="mt-3 opacity-70">
                                Big savings on top-rated products, while stocks
                                last.
                            </p>
                            <Button
                                size="lg"
                                variant="secondary"
                                className="mt-6"
                                asChild
                            >
                                <Link
                                    href={shopIndex({ query: { on_sale: 1 } })}
                                >
                                    Shop all deals
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </div>
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            {deals.map((product) => (
                                <Link
                                    key={product.id}
                                    href={productShow(product.slug)}
                                    className="group overflow-hidden rounded-xl bg-background text-foreground"
                                >
                                    <div className="relative">
                                        <ProductImage
                                            src={product.image}
                                            alt={product.name}
                                            className="aspect-square p-3"
                                            imgClassName="transition-transform group-hover:scale-105"
                                        />
                                        {product.discount_percentage && (
                                            <span className="absolute top-2 left-2 rounded-full bg-rose-600 px-2 py-0.5 text-xs font-semibold text-white">
                                                -{product.discount_percentage}%
                                            </span>
                                        )}
                                    </div>
                                    <div className="p-3">
                                        <p className="truncate text-sm font-medium">
                                            {product.name}
                                        </p>
                                        <p className="text-sm">
                                            <span className="font-semibold">
                                                {product.price.formatted}
                                            </span>{' '}
                                            {product.compare_at_price && (
                                                <span className="text-muted-foreground line-through">
                                                    {
                                                        product.compare_at_price
                                                            .formatted
                                                    }
                                                </span>
                                            )}
                                        </p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </Container>
                </section>
            )}

            {newArrivals.length > 0 && (
                <Container className="py-16">
                    <SectionHeading
                        eyebrow="Just landed"
                        title="New arrivals"
                        href={shopIndex({ query: { sort: 'newest' } })}
                    />
                    <ProductGrid products={newArrivals} />
                </Container>
            )}

            {bestSellers.length > 0 && (
                <Container className="pb-16">
                    <SectionHeading
                        eyebrow="Customer favourites"
                        title="Best sellers"
                        href={shopIndex({ query: { sort: 'popular' } })}
                    />
                    <ProductGrid products={bestSellers} />
                </Container>
            )}

            <Container className="pb-4">
                <SectionHeading
                    eyebrow="From the blog"
                    title="Tips, guides & inspiration"
                    href={blogIndex()}
                    linkLabel="Read the blog"
                />
                <Deferred
                    data="posts"
                    fallback={<ProductGridSkeleton count={3} />}
                >
                    <div className="grid gap-6 md:grid-cols-3">
                        {posts?.map((post) => (
                            <PostCard key={post.id} post={post} />
                        ))}
                    </div>
                </Deferred>
            </Container>
        </>
    );
}
