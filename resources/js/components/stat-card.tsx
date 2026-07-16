import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { LucideIcon } from 'lucide-react';

interface StatCardProps {
    /**
     * Card title
     */
    title: string;

    /**
     * Main value to display
     */
    value: string | number;

    /**
     * Optional description or subtitle
     */
    description?: string;

    /**
     * Optional icon component
     */
    icon?: LucideIcon;

    /**
     * Icon color classes
     * @default 'text-muted-foreground'
     */
    iconColor?: string;

    /**
     * Optional trend indicator (e.g., "+12%", "-5%")
     */
    trend?: string;

    /**
     * Trend direction for color coding
     */
    trendDirection?: 'up' | 'down' | 'neutral';

    /**
     * Additional CSS classes
     */
    className?: string;

    /**
     * Click handler
     */
    onClick?: () => void;
}

export function StatCard({
    title,
    value,
    description,
    icon: Icon,
    iconColor = 'text-muted-foreground',
    trend,
    trendDirection = 'neutral',
    className,
    onClick,
}: StatCardProps) {
    const trendColors = {
        up: 'text-green-600 dark:text-green-400',
        down: 'text-red-600 dark:text-red-400',
        neutral: 'text-muted-foreground',
    };

    return (
        <Card
            className={cn(
                'relative overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_93%,var(--color-accent)))]',
                'before:absolute before:inset-x-6 before:top-0 before:h-px before:bg-[image:var(--gradient-brand-soft)] before:content-[""]',
                onClick &&
                    'cursor-pointer transition-[transform,box-shadow,border-color] hover:-translate-y-0.5 hover:border-primary/15 hover:shadow-[0_26px_52px_-36px_rgb(96_44_193_/_0.3)]',
                className,
            )}
            onClick={onClick}
        >
            <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-3">
                <div className="space-y-1">
                    <CardTitle className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                        {title}
                    </CardTitle>
                    {trend && (
                        <span
                            className={cn(
                                'text-xs font-semibold',
                                trendColors[trendDirection],
                            )}
                        >
                            {trend}
                        </span>
                    )}
                </div>
                {Icon && (
                    <div className="flex size-10 items-center justify-center rounded-2xl bg-accent">
                        <Icon className={`h-4 w-4 ${iconColor}`} />
                    </div>
                )}
            </CardHeader>
            <CardContent className="space-y-3">
                <div className="text-3xl font-semibold tracking-[-0.05em] text-foreground">
                    {value}
                </div>
                {description && (
                    <div className="text-sm leading-6 text-muted-foreground">
                        {description}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
