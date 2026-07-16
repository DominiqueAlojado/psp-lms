import { Appearance, useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { LucideIcon, Monitor, Moon, Sun } from 'lucide-react';
import { HTMLAttributes } from 'react';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: 'Light' },
        { value: 'dark', icon: Moon, label: 'Dark' },
        { value: 'system', icon: Monitor, label: 'System' },
    ];

    return (
        <div
            className={cn(
                'inline-flex gap-1 rounded-2xl border border-border/80 bg-muted/85 p-1 shadow-[0_10px_24px_-22px_rgb(35_24_74_/_0.22)] dark:shadow-[0_18px_36px_-28px_rgb(0_0_0_/_0.7)]',
                className,
            )}
            {...props}
        >
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    onClick={() => updateAppearance(value)}
                    className={cn(
                        'flex items-center rounded-xl px-3.5 py-1.5 transition-[color,background-color,box-shadow]',
                        appearance === value
                            ? 'bg-background/96 text-foreground shadow-[0_10px_24px_-20px_rgb(35_24_74_/_0.28)] dark:shadow-[0_16px_28px_-22px_rgb(0_0_0_/_0.72)]'
                            : 'text-muted-foreground hover:bg-accent/80 hover:text-foreground',
                    )}
                >
                    <Icon className="-ml-1 h-4 w-4" />
                    <span className="ml-1.5 text-sm">{label}</span>
                </button>
            ))}
        </div>
    );
}
