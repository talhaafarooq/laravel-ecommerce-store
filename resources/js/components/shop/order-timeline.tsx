import { Check, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Order } from '@/types';

const steps = ['Placed', 'Processing', 'Shipped', 'Delivered'];

/** Horizontal progress tracker for the happy path of an order. */
export function OrderProgress({ order }: { order: Order }) {
    const cancelled = order.status.value === 'cancelled';

    if (cancelled) {
        return (
            <div className="flex items-center gap-3 rounded-lg border border-rose-500/30 bg-rose-50 p-4 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-300">
                <X className="size-5 shrink-0" />
                <div>
                    <p className="font-medium">This order was cancelled</p>
                    {order.cancellation_reason && (
                        <p className="opacity-80">{order.cancellation_reason}</p>
                    )}
                </div>
            </div>
        );
    }

    return (
        <ol className="grid grid-cols-4 gap-2" aria-label="Order progress">
            {steps.map((label, index) => {
                const done = order.status.step > index;
                const current = order.status.step === index + 1;

                return (
                    <li key={label} className="flex flex-col items-center gap-2 text-center">
                        <div className="flex w-full items-center">
                            <span className={cn('h-0.5 flex-1', index === 0 ? 'opacity-0' : done || current ? 'bg-primary' : 'bg-border')} />
                            <span
                                className={cn(
                                    'flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-xs font-semibold',
                                    done || current
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border bg-background text-muted-foreground',
                                    current && !done && 'ring-4 ring-primary/20',
                                )}
                            >
                                {done ? <Check className="size-4" /> : index + 1}
                            </span>
                            <span className={cn('h-0.5 flex-1', index === steps.length - 1 ? 'opacity-0' : done ? 'bg-primary' : 'bg-border')} />
                        </div>
                        <span className={cn('text-xs font-medium', done || current ? 'text-foreground' : 'text-muted-foreground')}>
                            {label}
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}
