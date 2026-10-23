import { usePage } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';
import { cn } from '@/lib/utils';

export function StoreLogo({ className }: { className?: string }) {
    const { name } = usePage().props;

    return (
        <span className={cn('flex items-center gap-2', className)}>
            <span className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <ShoppingBag className="size-4" />
            </span>
            <span className="text-lg font-semibold tracking-tight">{name}</span>
        </span>
    );
}
