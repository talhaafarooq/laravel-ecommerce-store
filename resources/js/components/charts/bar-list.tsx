import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

export type BarItem = {
    id: string | number;
    label: React.ReactNode;
    /** Plain-text label used in the tooltip and for assistive technology. */
    name: string;
    value: number;
    /** Formatted value, shown at the bar tip. */
    display: string;
    /** Secondary line under the label (e.g. a SKU). */
    hint?: string;
    href?: NonNullable<InertiaLinkProps['href']>;
};

/**
 * Horizontal bars for comparing magnitude across nominal categories.
 *
 * One series, so every bar wears the same accent (colour is never spent on
 * re-encoding bar length). Values sit at the bar tip as visible text, which
 * means the tooltip only ever adds the share, it never gates a number.
 */
export function BarList({
    items,
    total,
    emptyMessage = 'No data for this period.',
    className,
}: {
    items: BarItem[];
    /** When set, the tooltip also reports each bar's share of this total. */
    total?: number;
    emptyMessage?: string;
    className?: string;
}) {
    if (items.length === 0) {
        return (
            <p className="py-8 text-center text-sm text-muted-foreground">
                {emptyMessage}
            </p>
        );
    }

    const max = Math.max(...items.map((item) => item.value), 1);

    return (
        <ul className={cn('space-y-3.5', className)}>
            {items.map((item) => {
                const width = (item.value / max) * 100;
                const share =
                    total && total > 0
                        ? ` · ${((item.value / total) * 100).toFixed(1)}% of total`
                        : '';

                const label = item.href ? (
                    <Link
                        href={item.href}
                        className="truncate text-sm font-medium hover:text-primary"
                    >
                        {item.label}
                    </Link>
                ) : (
                    <span className="truncate text-sm font-medium">
                        {item.label}
                    </span>
                );

                return (
                    <li key={item.id}>
                        <div className="mb-1.5 flex min-w-0 items-baseline justify-between gap-3">
                            <div className="min-w-0">
                                {label}
                                {item.hint && (
                                    <p className="truncate text-xs text-muted-foreground">
                                        {item.hint}
                                    </p>
                                )}
                            </div>
                        </div>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <div
                                    tabIndex={0}
                                    role="img"
                                    className="group flex h-4 items-center rounded-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    aria-label={`${item.name}: ${item.display}${share}`}
                                >
                                    {/* The longest bar stops 5rem short of the edge so its value always fits at the tip. */}
                                    <div
                                        className="h-2.5 shrink-0 rounded-r-[4px] bg-chart-1 transition-opacity group-hover:opacity-75 group-focus-visible:opacity-75"
                                        style={{
                                            width: `calc((100% - 5rem) * ${width / 100})`,
                                            minWidth: item.value > 0 ? 4 : 0,
                                        }}
                                    />
                                    <span className="ml-2 text-sm font-semibold whitespace-nowrap">
                                        {item.display}
                                    </span>
                                </div>
                            </TooltipTrigger>
                            <TooltipContent side="top" align="start">
                                <span className="font-semibold">
                                    {item.display}
                                </span>{' '}
                                <span className="opacity-80">
                                    {item.name}
                                    {share}
                                </span>
                            </TooltipContent>
                        </Tooltip>
                    </li>
                );
            })}
        </ul>
    );
}
