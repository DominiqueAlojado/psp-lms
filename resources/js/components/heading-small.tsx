import { cn } from '@/lib/utils';

export default function HeadingSmall({
    title,
    description,
    className,
}: {
    title: string;
    description?: string;
    className?: string;
}) {
    return (
        <header className={cn('space-y-2', className)}>
            <h3 className="text-2xl font-semibold tracking-[-0.03em] text-foreground sm:text-[1.75rem]">
                {title}
            </h3>
            {description && (
                <p className="max-w-3xl text-sm leading-6 text-muted-foreground sm:text-[0.95rem]">
                    {description}
                </p>
            )}
        </header>
    );
}
