import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react';
import { TableHead } from '@/components/ui/table';
import { cn } from '@/lib/utils';

export function SortableHead({
    column,
    label,
    sort,
    direction,
    onSort,
    className,
}: {
    column: string;
    label: string;
    sort: string;
    direction: string;
    onSort: (column: string, direction: 'asc' | 'desc') => void;
    className?: string;
}) {
    const active = sort === column;
    const Icon = !active
        ? ChevronsUpDown
        : direction === 'asc'
          ? ArrowUp
          : ArrowDown;

    return (
        <TableHead
            className={className}
            aria-sort={
                active
                    ? direction === 'asc'
                        ? 'ascending'
                        : 'descending'
                    : 'none'
            }
        >
            <button
                type="button"
                onClick={() =>
                    onSort(
                        column,
                        active && direction === 'desc' ? 'asc' : 'desc',
                    )
                }
                className={cn(
                    'inline-flex items-center gap-1 tracking-wide uppercase hover:text-foreground',
                    active && 'text-foreground',
                )}
            >
                {label}
                <Icon className="size-3.5" />
            </button>
        </TableHead>
    );
}
