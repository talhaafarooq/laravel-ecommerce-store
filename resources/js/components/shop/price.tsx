import { cn } from '@/lib/utils';
import type { Money } from '@/types';

export function Price({
    price,
    compareAt,
    size = 'md',
    className,
}: {
    price: Money;
    compareAt?: Money | null;
    size?: 'sm' | 'md' | 'lg';
    className?: string;
}) {
    return (
        <div className={cn('flex flex-wrap items-baseline gap-x-2', className)}>
            <span
                className={cn(
                    'font-semibold tracking-tight text-foreground',
                    size === 'sm' && 'text-sm',
                    size === 'md' && 'text-base',
                    size === 'lg' && 'text-3xl',
                )}
            >
                {price.formatted}
            </span>
            {compareAt && (
                <span
                    className={cn(
                        'text-muted-foreground line-through',
                        size === 'lg' ? 'text-lg' : 'text-sm',
                    )}
                >
                    <span className="sr-only">Was </span>
                    {compareAt.formatted}
                </span>
            )}
        </div>
    );
}
