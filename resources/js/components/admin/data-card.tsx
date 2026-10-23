import { usePageLoading } from '@/hooks/use-page-loading';
import { cn } from '@/lib/utils';

/** Bordered surface for tables and lists in the admin area. */
export function DataCard({
    title,
    description,
    actions,
    children,
    className,
    flush = false,
}: {
    title?: string;
    description?: string;
    actions?: React.ReactNode;
    children: React.ReactNode;
    className?: string;
    /** Removes the body padding, e.g. for edge-to-edge tables. */
    flush?: boolean;
}) {
    // Hold the previous render (dimmed) while a refetch is running: no skeleton, no layout jump.
    const loading = usePageLoading();

    return (
        <section
            className={cn(
                'rounded-xl border bg-card transition-opacity',
                loading && 'opacity-60',
                className,
            )}
            aria-busy={loading}
        >
            {(title || actions) && (
                <header className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
                    <div>
                        {title && <h2 className="font-semibold">{title}</h2>}
                        {description && (
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    {actions}
                </header>
            )}
            <div className={cn(!flush && 'p-5')}>{children}</div>
        </section>
    );
}
