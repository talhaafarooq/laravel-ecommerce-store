import { Separator } from '@/components/ui/separator';
import type { OrderTotals as Totals } from '@/types';

export function OrderTotals({
    totals,
    couponCode,
}: {
    totals: Totals;
    couponCode?: string | null;
}) {
    return (
        <dl className="space-y-3 text-sm">
            <Row label="Subtotal" value={totals.subtotal.formatted} />
            {totals.discount.amount > 0 && (
                <Row
                    label={couponCode ? `Discount (${couponCode})` : 'Discount'}
                    value={`−${totals.discount.formatted}`}
                    className="text-emerald-600 dark:text-emerald-400"
                />
            )}
            <Row
                label="Shipping"
                value={
                    totals.shipping.amount === 0
                        ? 'Free'
                        : totals.shipping.formatted
                }
            />
            {totals.tax.amount > 0 && (
                <Row label="Tax" value={totals.tax.formatted} />
            )}
            <Separator />
            <div className="flex items-center justify-between text-base font-semibold">
                <dt>Total</dt>
                <dd>{totals.total.formatted}</dd>
            </div>
        </dl>
    );
}

function Row({
    label,
    value,
    className,
}: {
    label: string;
    value: string;
    className?: string;
}) {
    return (
        <div className={`flex items-center justify-between ${className ?? ''}`}>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}
