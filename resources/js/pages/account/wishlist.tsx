import { Head, Link } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import { EmptyState } from '@/components/shop/empty-state';
import { Pagination } from '@/components/shop/pagination';
import { ProductGrid } from '@/components/shop/product-card';
import { Button } from '@/components/ui/button';
import { index as shopIndex } from '@/routes/shop';
import type { Paginated, ProductCard } from '@/types';

export default function Wishlist({
    products,
}: {
    products: Paginated<ProductCard>;
}) {
    return (
        <>
            <Head title="Wishlist" />
            <h2 className="mb-6 text-xl font-semibold">Your wishlist</h2>
            {products.data.length === 0 ? (
                <EmptyState
                    icon={Heart}
                    title="Nothing saved yet"
                    description="Tap the heart on any product to save it here for later."
                    action={
                        <Button asChild>
                            <Link href={shopIndex()}>Discover products</Link>
                        </Button>
                    }
                />
            ) : (
                <>
                    <ProductGrid
                        products={products.data}
                        className="xl:grid-cols-3"
                    />
                    <Pagination meta={products.meta} className="mt-8" />
                </>
            )}
        </>
    );
}
