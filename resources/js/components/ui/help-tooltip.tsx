import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { CircleHelp } from 'lucide-react';

interface HelpTooltipProps {
    content: string;
    ariaLabel?: string;
    className?: string;
    contentClassName?: string;
}

export function HelpTooltip({
    content,
    ariaLabel = 'Explain item',
    className,
    contentClassName,
}: HelpTooltipProps) {
    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger asChild>
                    <button
                        type="button"
                        className={cn(
                            'inline-flex h-5 w-5 items-center justify-center rounded-full text-muted-foreground transition hover:bg-accent/60 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30',
                            className,
                        )}
                        aria-label={ariaLabel}
                    >
                        <CircleHelp className="size-3.5" />
                    </button>
                </TooltipTrigger>
                <TooltipContent
                    sideOffset={8}
                    className={cn(
                        'max-w-[18rem] rounded-xl border border-primary/15 bg-primary px-3 py-2 text-[11px] leading-relaxed text-primary-foreground shadow-[0_18px_36px_-24px_rgb(0_0_0_/_0.45)]',
                        contentClassName,
                    )}
                >
                    {content}
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}
