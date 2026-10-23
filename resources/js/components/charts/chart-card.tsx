import { BarChart3, Table2 } from 'lucide-react';
import { useId, useState } from 'react';
import { SegmentedControl } from '@/components/admin/segmented-control';
import { cn } from '@/lib/utils';

type View = 'chart' | 'table';

/**
 * Card around a chart that offers a table twin, so every value is reachable
 * without hovering and without relying on colour.
 */
export function ChartCard({
    title,
    description,
    actions,
    chart,
    table,
    className,
}: {
    title: string;
    description?: string;
    actions?: React.ReactNode;
    chart: React.ReactNode;
    table: React.ReactNode;
    className?: string;
}) {
    const [view, setView] = useState<View>('chart');
    const panelId = useId();

    return (
        <section className={cn('rounded-xl border bg-card', className)}>
            <header className="flex flex-wrap items-start justify-between gap-3 p-5 pb-2">
                <div>
                    <h2 className="font-semibold">{title}</h2>
                    {description && (
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                <div className="flex items-center gap-2">
                    {actions}
                    <SegmentedControl
                        label={`${title} view`}
                        controls={panelId}
                        value={view}
                        onChange={setView}
                        options={[
                            { value: 'chart', label: 'Chart', icon: BarChart3 },
                            { value: 'table', label: 'Table', icon: Table2 },
                        ]}
                    />
                </div>
            </header>
            <div id={panelId} className="px-5 pb-5">
                {view === 'chart' ? (
                    chart
                ) : (
                    <div className="max-h-80 overflow-auto rounded-lg border">
                        {table}
                    </div>
                )}
            </div>
        </section>
    );
}
