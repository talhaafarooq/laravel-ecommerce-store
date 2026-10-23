import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export type SegmentedOption<T extends string> = {
    value: T;
    label: string;
    icon?: LucideIcon;
};

/** Compact toggle for switching between a few views of the same data. */
export function SegmentedControl<T extends string>({
    options,
    value,
    onChange,
    label,
    controls,
}: {
    options: SegmentedOption<T>[];
    value: T;
    onChange: (value: T) => void;
    label: string;
    controls?: string;
}) {
    return (
        <div
            role="group"
            aria-label={label}
            className="inline-flex rounded-lg bg-muted p-0.5"
        >
            {options.map(({ value: optionValue, label: text, icon: Icon }) => (
                <button
                    key={optionValue}
                    type="button"
                    aria-pressed={value === optionValue}
                    aria-controls={controls}
                    onClick={() => onChange(optionValue)}
                    className={cn(
                        'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                        value === optionValue
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    {Icon && <Icon className="size-3.5" />}
                    {text}
                </button>
            ))}
        </div>
    );
}
