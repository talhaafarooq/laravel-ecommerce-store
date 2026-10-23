import { Minus, Plus } from 'lucide-react';
import { cn } from '@/lib/utils';

export function QuantityInput({
    value,
    max,
    onChange,
    disabled = false,
    size = 'md',
}: {
    value: number;
    max: number;
    onChange: (value: number) => void;
    disabled?: boolean;
    size?: 'sm' | 'md';
}) {
    const clamp = (next: number) => Math.max(1, Math.min(max, next));
    const button = cn(
        'inline-flex items-center justify-center text-muted-foreground transition-colors hover:text-foreground disabled:opacity-40',
        size === 'sm' ? 'size-8' : 'size-10',
    );

    return (
        <div className="inline-flex items-center rounded-md border bg-background">
            <button
                type="button"
                className={button}
                onClick={() => onChange(clamp(value - 1))}
                disabled={disabled || value <= 1}
                aria-label="Decrease quantity"
            >
                <Minus className="size-4" />
            </button>
            <input
                type="number"
                inputMode="numeric"
                min={1}
                max={max}
                value={value}
                disabled={disabled}
                aria-label="Quantity"
                onChange={(event) =>
                    onChange(clamp(Number(event.target.value) || 1))
                }
                className={cn(
                    'w-10 [appearance:textfield] border-x bg-transparent text-center text-sm font-medium outline-none [&::-webkit-inner-spin-button]:appearance-none',
                    size === 'sm' ? 'h-8' : 'h-10',
                )}
            />
            <button
                type="button"
                className={button}
                onClick={() => onChange(clamp(value + 1))}
                disabled={disabled || value >= max}
                aria-label="Increase quantity"
            >
                <Plus className="size-4" />
            </button>
        </div>
    );
}
