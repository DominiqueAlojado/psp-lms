import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartContainer, type ChartConfig } from '@/components/ui/chart';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface RechartsShellProps {
    title: string;
    description?: string;
    config: ChartConfig;
    children: ReactNode;
    className?: string;
    chartClassName?: string;
    contentClassName?: string;
}

export function RechartsShell({
    title,
    description,
    config,
    children,
    className,
    chartClassName,
    contentClassName,
}: RechartsShellProps) {
    return (
        <Card
            className={cn(
                'overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]',
                className,
            )}
        >
            <CardHeader className="pb-3">
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent className={cn('space-y-3', contentClassName)}>
                <ChartContainer
                    config={config}
                    className={cn('h-80 w-full', chartClassName)}
                >
                    {children}
                </ChartContainer>
                {description ? (
                    <p className="text-sm text-muted-foreground">{description}</p>
                ) : null}
            </CardContent>
        </Card>
    );
}
