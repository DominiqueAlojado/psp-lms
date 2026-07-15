import { cn } from '@/lib/utils';

export default function Heading({
    title,
    description,
    className,
}: {
    title: string;
    description?: string;
    className?: string;
}) {
    return (
        <div className={cn('mb-8 space-y-2', className)}>
            <h2 className="text-3xl font-semibold tracking-[-0.04em] text-foreground">
                {title}
            </h2>
            {description && (
                <p className="max-w-3xl text-sm leading-6 text-muted-foreground sm:text-[0.95rem]">
                    {description}
                </p>
            )}
        </div>
    );
}
