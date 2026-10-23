import { Link } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import { formatDate } from '@/lib/format';
import { show } from '@/routes/blog';
import type { Post } from '@/types';

export function PostCard({ post }: { post: Post }) {
    return (
        <article className="group flex flex-col overflow-hidden rounded-xl border bg-card">
            <Link href={show(post.slug)} className="block overflow-hidden">
                <div className="aspect-[16/9] bg-muted">
                    {post.cover && (
                        <img
                            src={post.cover}
                            alt=""
                            loading="lazy"
                            className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                    )}
                </div>
            </Link>
            <div className="flex flex-1 flex-col p-5">
                <div className="flex items-center gap-3 text-xs text-muted-foreground">
                    <time dateTime={post.published_at ?? undefined}>
                        {formatDate(post.published_at)}
                    </time>
                    <span className="flex items-center gap-1">
                        <Clock className="size-3.5" />
                        {post.reading_time} min read
                    </span>
                </div>
                <h3 className="mt-2 text-lg leading-snug font-semibold">
                    <Link href={show(post.slug)} className="hover:text-primary">
                        {post.title}
                    </Link>
                </h3>
                {post.excerpt && (
                    <p className="mt-2 line-clamp-3 text-sm text-muted-foreground">
                        {post.excerpt}
                    </p>
                )}
            </div>
        </article>
    );
}
