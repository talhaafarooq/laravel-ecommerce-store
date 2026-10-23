import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

export function Pagination({
    meta,
    className,
    only,
}: {
    meta: Paginated<unknown>['meta'];
    className?: string;
    only?: string[];
}) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <nav
            aria-label="Pagination"
            className={cn(
                'flex flex-col items-center justify-between gap-3 sm:flex-row',
                className,
            )}
        >
            <p className="text-sm text-muted-foreground">
                Showing <span className="font-medium">{meta.from}</span>–
                <span className="font-medium">{meta.to}</span> of{' '}
                <span className="font-medium">{meta.total}</span>
            </p>
            <div className="flex flex-wrap items-center gap-1">
                {meta.links.map((link, index) => {
                    const isPrev = index === 0;
                    const isNext = index === meta.links.length - 1;
                    const label = isPrev ? (
                        <ChevronLeft className="size-4" />
                    ) : isNext ? (
                        <ChevronRight className="size-4" />
                    ) : (
                        link.label
                    );

                    const classes = cn(
                        'inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm transition-colors',
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'bg-background hover:bg-accent',
                        !link.url && 'pointer-events-none opacity-40',
                    );

                    if (!link.url || link.label === '...') {
                        return (
                            <span
                                key={index}
                                className={classes}
                                aria-hidden={!link.url}
                            >
                                {label}
                            </span>
                        );
                    }

                    return (
                        <Link
                            key={index}
                            href={link.url}
                            only={only}
                            preserveScroll={only !== undefined}
                            className={classes}
                            aria-current={link.active ? 'page' : undefined}
                            aria-label={
                                isPrev
                                    ? 'Previous page'
                                    : isNext
                                      ? 'Next page'
                                      : `Page ${link.label}`
                            }
                        >
                            {label}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
