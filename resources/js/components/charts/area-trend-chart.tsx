import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import type { DotItemDotProps, TooltipContentProps } from 'recharts';
import { useIsClient } from '@/hooks/use-is-client';

export type TrendPoint = { date: string } & Record<string, number | string>;

type Props = {
    data: TrendPoint[];
    /** The numeric field plotted on the y axis. */
    dataKey: string;
    /** Human name of the metric, shown in the tooltip. */
    label: string;
    /** Formats a value for the tooltip. */
    formatValue: (value: number) => string;
    /** Formats a value for the y axis ticks (compact). */
    formatTick?: (value: number) => string;
    height?: number;
};

const dayFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
});

const longDayFormatter = new Intl.DateTimeFormat('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
});

/** Dates arrive as `YYYY-MM-DD`; parse them as local days to avoid timezone drift. */
const toDate = (value: string | number | undefined): Date =>
    new Date(`${String(value)}T00:00:00`);

export const compactNumber = new Intl.NumberFormat('en-US', {
    notation: 'compact',
    maximumFractionDigits: 1,
});

/**
 * Single-series trend: 2px line, ~10% wash, hairline grid, a crosshair that
 * snaps to the nearest day, and an end marker with a surface ring.
 */
export function AreaTrendChart({
    data,
    dataKey,
    label,
    formatValue,
    formatTick = (value) => compactNumber.format(value),
    height = 280,
}: Props) {
    const isClient = useIsClient();

    if (!isClient) {
        return <div style={{ height }} aria-hidden />;
    }

    return (
        <div
            style={{ height }}
            role="img"
            aria-label={`${label} over time. A table view is available.`}
        >
            <ResponsiveContainer width="100%" height="100%">
                <AreaChart
                    data={data}
                    margin={{ top: 12, right: 16, bottom: 0, left: 0 }}
                >
                    <CartesianGrid
                        vertical={false}
                        stroke="var(--chart-grid)"
                        strokeWidth={1}
                    />
                    <XAxis
                        dataKey="date"
                        tickFormatter={(value: string) =>
                            dayFormatter.format(toDate(value))
                        }
                        tickLine={false}
                        axisLine={{
                            stroke: 'var(--chart-axis)',
                            strokeWidth: 1,
                        }}
                        tick={{ fill: 'var(--chart-label)', fontSize: 12 }}
                        tickMargin={10}
                        minTickGap={36}
                    />
                    <YAxis
                        tickFormatter={formatTick}
                        tickLine={false}
                        axisLine={false}
                        tick={{ fill: 'var(--chart-label)', fontSize: 12 }}
                        width={48}
                        allowDecimals={false}
                    />
                    <Tooltip
                        isAnimationActive={false}
                        cursor={{ stroke: 'var(--chart-axis)', strokeWidth: 1 }}
                        content={(props) => (
                            <TrendTooltip
                                {...props}
                                label={props.label}
                                name={label}
                                formatValue={formatValue}
                            />
                        )}
                    />
                    <Area
                        type="monotone"
                        dataKey={dataKey}
                        name={label}
                        stroke="var(--chart-1)"
                        strokeWidth={2}
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        fill="var(--chart-1)"
                        fillOpacity={0.1}
                        isAnimationActive={false}
                        dot={(props: DotItemDotProps) => (
                            <EndDot
                                key={props.index}
                                {...props}
                                last={props.index === data.length - 1}
                            />
                        )}
                        activeDot={{
                            r: 5,
                            fill: 'var(--chart-1)',
                            stroke: 'var(--card)',
                            strokeWidth: 2,
                        }}
                    />
                </AreaChart>
            </ResponsiveContainer>
        </div>
    );
}

function EndDot({
    cx,
    cy,
    last,
}: Pick<DotItemDotProps, 'cx' | 'cy'> & { last: boolean }) {
    if (!last || cx === undefined || cy === undefined) {
        return <g />;
    }

    return (
        <circle
            cx={cx}
            cy={cy}
            r={5}
            fill="var(--chart-1)"
            stroke="var(--card)"
            strokeWidth={2}
        />
    );
}

function TrendTooltip({
    active,
    payload,
    label,
    name,
    formatValue,
}: TooltipContentProps & {
    name: string;
    formatValue: (value: number) => string;
}) {
    if (!active || !payload?.length) {
        return null;
    }

    const value = Number(payload[0].value ?? 0);

    return (
        <div className="min-w-40 rounded-lg border bg-popover px-3 py-2 text-popover-foreground shadow-md">
            <p className="text-xs text-muted-foreground">
                {longDayFormatter.format(toDate(label))}
            </p>
            <div className="mt-1.5 flex items-center gap-2">
                {/* Line key: a short stroke of the series colour, not a box. */}
                <span
                    aria-hidden
                    className="h-0.5 w-3 rounded-full bg-chart-1"
                />
                <span className="text-base font-semibold tabular-nums">
                    {formatValue(value)}
                </span>
                <span className="text-xs text-muted-foreground">{name}</span>
            </div>
        </div>
    );
}
