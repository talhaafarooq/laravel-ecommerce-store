import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

export function SectionHeading({
    eyebrow,
    title,
    description,
    href,
    linkLabel = 'View all',
}: {
    eyebrow?: string;
    title: string;
    description?: string;
    href?: NonNullable<InertiaLinkProps['href']>;
    linkLabel?: string;
}) {
    return (
        <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                {eyebrow && (
                    <p className="mb-1 text-xs font-semibold tracking-widest text-primary uppercase">
                        {eyebrow}
                    </p>
                )}
                <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                    {title}
                </h2>
                {description && (
                    <p className="mt-1 text-muted-foreground">{description}</p>
                )}
            </div>
            {href && (
                <Link
                    href={href}
                    className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                >
                    {linkLabel}
                    <ArrowRight className="size-4" />
                </Link>
            )}
        </div>
    );
}
