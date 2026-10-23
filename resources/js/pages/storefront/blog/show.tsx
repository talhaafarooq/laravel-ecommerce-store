import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Clock } from 'lucide-react';
import { Container } from '@/components/shop/container';
import { PostCard } from '@/components/shop/post-card';
import { formatDate } from '@/lib/format';
import { index } from '@/routes/blog';
import type { Post } from '@/types';

export default function BlogShow({ post, related }: { post: Post; related: Post[] }) {
    return (
        <>
            <Head title={post.meta_title}>
                <meta name="description" content={post.meta_description ?? ''} />
                <meta property="og:type" content="article" />
                <meta property="og:title" content={post.title} />
                {post.cover && <meta property="og:image" content={post.cover} />}
            </Head>

            <Container className="max-w-3xl py-10 lg:py-14">
                <Link
                    href={index()}
                    className="mb-6 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    All articles
                </Link>
                <article>
                    <header>
                        <h1 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{post.title}</h1>
                        <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                            {post.author && <span>By {post.author.name}</span>}
                            <time dateTime={post.published_at ?? undefined}>{formatDate(post.published_at)}</time>
                            <span className="flex items-center gap-1">
                                <Clock className="size-4" />
                                {post.reading_time} min read
                            </span>
                        </div>
                    </header>
                    {post.cover && (
                        <img
                            src={post.cover}
                            alt=""
                            className="mt-8 aspect-[16/9] w-full rounded-2xl border object-cover"
                        />
                    )}
                    <div
                        className="rich-text mt-8"
                        // Server-rendered, sanitised Markdown (raw HTML is stripped).
                        dangerouslySetInnerHTML={{ __html: post.body_html ?? '' }}
                    />
                </article>
            </Container>

            {related.length > 0 && (
                <section className="border-t bg-muted/30 py-12">
                    <Container>
                        <h2 className="mb-6 text-2xl font-semibold tracking-tight">Keep reading</h2>
                        <div className="grid gap-6 md:grid-cols-3">
                            {related.map((item) => (
                                <PostCard key={item.id} post={item} />
                            ))}
                        </div>
                    </Container>
                </section>
            )}
        </>
    );
}
