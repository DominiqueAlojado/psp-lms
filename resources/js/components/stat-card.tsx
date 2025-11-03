import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
            className={`${onClick ? 'cursor-pointer transition-colors hover:bg-accent' : ''} ${className || ''}`}
            onClick={onClick}
        >
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                {Icon && <Icon className={`h-4 w-4 ${iconColor}`} />}
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                {(description || trend) && (
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        {description && <span>{description}</span>}
                        {trend && (
                            <span className={trendColors[trendDirection]}>
                                {trend}
                            </span>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

