import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export function StatCard({
    label,
    value,
    icon: Icon,
    hint,
    className,
}: {
    label: string;
    value: React.ReactNode;
    icon: LucideIcon;
    hint?: React.ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('rounded-xl border bg-card p-5', className)}>
            <div className="flex items-center justify-between">
                <p className="text-sm text-muted-foreground">{label}</p>
                <span className="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Icon className="size-4" />
                </span>
            </div>
            <p className="mt-3 text-2xl font-semibold tracking-tight">{value}</p>
            {hint && <div className="mt-1 text-xs text-muted-foreground">{hint}</div>}
        </div>
    );
}
