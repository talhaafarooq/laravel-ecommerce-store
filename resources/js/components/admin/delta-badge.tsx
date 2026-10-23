import { ArrowDownRight, ArrowUpRight, Minus } from 'lucide-react';
import { StatusBadge } from '@/components/shop/status-badge';

/**
 * Signed change versus the previous period. Direction is carried by the arrow
 * and the sign as well as the colour, so it never relies on colour alone.
 */
export function DeltaBadge({
    change,
    period = 'vs previous 30 days',
}: {
    change: number | null;
    period?: string;
}) {
    if (change === null) {
        return (
            <span className="text-xs text-muted-foreground">
                No earlier data to compare
            </span>
        );
    }

    const tone = change > 0 ? 'success' : change < 0 ? 'danger' : 'neutral';
    const Icon =
        change > 0 ? ArrowUpRight : change < 0 ? ArrowDownRight : Minus;

    return (
        <span className="inline-flex flex-wrap items-center gap-1.5">
            <StatusBadge tone={tone}>
                <Icon className="size-3" />
                {change > 0 ? '+' : ''}
                {change}%
            </StatusBadge>
            <span className="text-xs text-muted-foreground">{period}</span>
        </span>
    );
}
