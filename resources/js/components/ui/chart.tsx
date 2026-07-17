import * as React from 'react';
import * as RechartsPrimitive from 'recharts';

import { cn } from '@/lib/utils';

export type ChartConfig = {
    [key: string]: {
        label?: React.ReactNode;
        icon?: React.ComponentType;
        color?: string;
    };
};

type ChartContextProps = {
    config: ChartConfig;
};

const ChartContext = React.createContext<ChartContextProps | null>(null);

function useChart() {
    const context = React.useContext(ChartContext);

    if (!context) {
        throw new Error('useChart must be used within a <ChartContainer />');
    }

    return context;
}

function ChartContainer({
    id,
    className,
    children,
    config,
    ...props
}: React.ComponentProps<'div'> & {
    config: ChartConfig;
    children: React.ComponentProps<
        typeof RechartsPrimitive.ResponsiveContainer
    >['children'];
}) {
    const uniqueId = React.useId();
    const chartId = `chart-${id || uniqueId.replace(/:/g, '')}`;

    return (
        <ChartContext.Provider value={{ config }}>
            <div
                data-slot="chart"
                data-chart={chartId}
                className={cn(
                    '[&_.recharts-cartesian-axis-tick_text]:fill-muted-foreground [&_.recharts-cartesian-grid_line[stroke="#ccc"]]:stroke-border [&_.recharts-curve.recharts-tooltip-cursor]:stroke-border [&_.recharts-dot[stroke="#fff"]]:stroke-transparent [&_.recharts-layer]:outline-none [&_.recharts-polar-grid_[stroke="#ccc"]]:stroke-border [&_.recharts-radial-bar-background-sector]:fill-muted [&_.recharts-rectangle.recharts-tooltip-cursor]:fill-muted [&_.recharts-reference-line_[stroke="#ccc"]]:stroke-border flex aspect-video justify-center text-xs',
                    className,
                )}
                {...props}
            >
                <ChartStyle id={chartId} config={config} />
                <RechartsPrimitive.ResponsiveContainer>
                    {children}
                </RechartsPrimitive.ResponsiveContainer>
            </div>
        </ChartContext.Provider>
    );
}

function ChartStyle({
    id,
    config,
}: {
    id: string;
    config: ChartConfig;
}) {
    const colorConfig = Object.entries(config).filter(
        ([, itemConfig]) => itemConfig.color,
    );

    if (!colorConfig.length) {
        return null;
    }

    return (
        <style
            dangerouslySetInnerHTML={{
                __html: `
[data-chart=${id}] {
${colorConfig
    .map(([key, itemConfig]) => `  --color-${key}: ${itemConfig.color};`)
    .join('\n')}
}
                `,
            }}
        />
    );
}

const ChartTooltip = RechartsPrimitive.Tooltip;

function ChartTooltipContent({
    active,
    payload,
    className,
    label,
    labelFormatter,
    formatter,
}: React.ComponentProps<typeof RechartsPrimitive.Tooltip> & {
    hideLabel?: boolean;
    hideIndicator?: boolean;
}) {
    const { config } = useChart();

    if (!active || !payload?.length) {
        return null;
    }

    const configuredPayload = payload.filter((item) => {
        const key = String(item.dataKey ?? item.name ?? '');
        return Boolean(config[key]);
    });
    const visiblePayload = configuredPayload.length ? configuredPayload : payload;

    const tooltipLabel = labelFormatter ? labelFormatter(label, visiblePayload) : label;

    return (
        <div
            className={cn(
                'grid min-w-44 gap-2 rounded-lg border bg-background px-3 py-2 text-xs shadow-md',
                className,
            )}
        >
            {tooltipLabel ? (
                <div className="font-medium text-foreground">{String(tooltipLabel)}</div>
            ) : null}
            <div className="grid gap-1.5">
                {visiblePayload.map((item) => {
                    const key = String(item.dataKey ?? item.name ?? '');
                    const itemConfig = config[key];

                    return (
                        <div
                            key={key}
                            className="flex items-center justify-between gap-3"
                        >
                            <div className="flex items-center gap-2">
                                <span
                                    className="h-2.5 w-2.5 shrink-0 rounded-[2px]"
                                    style={{
                                        backgroundColor:
                                            item.color ?? item.payload?.fill ?? 'currentColor',
                                    }}
                                />
                                <span className="text-muted-foreground">
                                    {itemConfig?.label ?? item.name}
                                </span>
                            </div>
                            <span className="font-medium text-foreground">
                                {formatter
                                    ? formatter(item.value, item.name, item, 0)
                                    : String(item.value ?? '')}
                            </span>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export { ChartContainer, ChartTooltip, ChartTooltipContent };
