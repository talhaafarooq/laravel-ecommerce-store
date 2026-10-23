import { toneClasses } from '@/lib/tone';
import { cn } from '@/lib/utils';
import type { Tone } from '@/types';

export function StatusBadge({
    tone,
    children,
    className,
}: {
    tone: Tone;
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset',
                toneClasses[tone],
                className,
            )}
        >
            {children}
        </span>
    );
}
