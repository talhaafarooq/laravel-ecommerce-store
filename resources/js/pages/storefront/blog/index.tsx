import { Head, router } from '@inertiajs/react';
import { Newspaper, Search } from 'lucide-react';
import { useState } from 'react';
import { Container } from '@/components/shop/container';
import { EmptyState } from '@/components/shop/empty-state';
import { Pagination } from '@/components/shop/pagination';
import { PostCard } from '@/components/shop/post-card';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/blog';
import type { Paginated, Post } from '@/types';

export default function BlogIndex({
    posts,
    search,
}: {
    posts: Paginated<Post>;
    search: string | null;
}) {
    const [query, setQuery] = useState(search ?? '');

    return (
        <>
            <Head title="Blog">
                <meta
                    name="description"
                    content="Buying guides, tips and inspiration from our team."
                />
            </Head>
            <div className="border-b bg-muted/40">
                <Container className="py-12 text-center">
                    <h1 className="text-4xl font-semibold tracking-tight">The Blog</h1>
                    <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
                        Buying guides, tips and inspiration from our team.
                    </p>
                    <form
                        className="relative mx-auto mt-6 max-w-md"
                        onSubmit={(event) => {
                            event.preventDefault();
                            router.get(index.url(), query ? { q: query } : {}, { preserveState: true });
                        }}
                    >
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Search articles…"
                            aria-label="Search articles"
                            className="rounded-full pl-9"
                        />
                    </form>
                </Container>
            </div>
            <Container className="py-12">
                {posts.data.length === 0 ? (
                    <EmptyState icon={Newspaper} title="No articles found" description="Try a different search term." />
                ) : (
                    <>
                        <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                            {posts.data.map((post) => (
                                <PostCard key={post.id} post={post} />
                            ))}
                        </div>
                        <Pagination meta={posts.meta} className="mt-12" />
                    </>
                )}
            </Container>
        </>
    );
}
