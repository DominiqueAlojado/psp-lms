import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Clock, Flag } from 'lucide-react';

interface QuestionPaletteLegendProps {
    answeredCount: number;
    notAnsweredCount: number;
    markedCount: number;
    timeRemaining: number | null;
    durationMinutes: number | null;
    formatTime: (seconds: number) => string;
    onSubmit: () => void;
    isSaving?: boolean;
}

export function QuestionPaletteLegend({
    answeredCount,
    notAnsweredCount,
    markedCount,
    timeRemaining,
    durationMinutes,
    formatTime,
    onSubmit,
    isSaving = false,
}: QuestionPaletteLegendProps) {
    return (
        <div className="bg-muted/30 px-3 py-3 sm:px-4 sm:py-4 md:px-4 md:py-6">
            {/* Mobile Layout: Stacked */}
            <div className="flex flex-col gap-3 md:hidden">
                {/* Saving Indicator */}
                {isSaving && (
                    <div className="flex items-center justify-center gap-2 text-xs text-muted-foreground">
                        <div className="h-2 w-2 animate-pulse rounded-full bg-blue-500" />
                        <span>Saving...</span>
                    </div>
                )}
                {/* Timer and Submit Button Row */}
                <div className="flex items-center justify-between gap-2">
                    {durationMinutes && timeRemaining !== null && (
                        <div className="flex items-center gap-1.5 rounded-lg border bg-background px-2.5 py-1.5 sm:gap-2 sm:px-3 sm:py-2">
                            <Clock className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                            <span
                                className={cn(
                                    'font-mono text-sm font-bold sm:text-base',
                                    timeRemaining < 300 && 'text-destructive',
                                )}
                            >
                                {formatTime(timeRemaining)}
                            </span>
                        </div>
                    )}
                    <Button
                        onClick={onSubmit}
                        size="sm"
                        className="sm:size-default"
                    >
                        Submit
                    </Button>
                </div>

                {/* Status Indicators - Wrapped */}
                <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                    <div className="flex items-center gap-1.5">
                        <div className="h-4 w-4 rounded border-2 border-green-500 bg-green-500/20" />
                        <span className="text-xs font-medium">
                            Ans ({answeredCount})
                        </span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="h-4 w-4 rounded border-2 border-muted-foreground" />
                        <span className="text-xs font-medium">
                            Not ({notAnsweredCount})
                        </span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="h-4 w-4 rounded border-2 border-primary bg-primary/20" />
                        <span className="text-xs font-medium">Current</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="flex h-4 w-4 items-center justify-center rounded border-2 border-orange-500 bg-orange-500/20">
                            <Flag className="h-2.5 w-2.5 fill-orange-500 text-orange-500" />
                        </div>
                        <span className="text-xs font-medium">
                            Mark ({markedCount})
                        </span>
                    </div>
                </div>
            </div>

            {/* Desktop Layout: Horizontal */}
            <div className="hidden items-center justify-between md:flex">
                {/* Saving Indicator */}
                {isSaving && (
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <div className="h-2 w-2 animate-pulse rounded-full bg-blue-500" />
                        <span>Saving...</span>
                    </div>
                )}
                {durationMinutes && timeRemaining !== null && (
                    <div className="flex items-center gap-2 rounded-lg border bg-background px-4 py-2">
                        <Clock className="h-4 w-4" />
                        <span
                            className={cn(
                                'font-mono text-lg font-bold',
                                timeRemaining < 300 && 'text-destructive',
                            )}
                        >
                            {formatTime(timeRemaining)}
                        </span>
                    </div>
                )}
                <div className="flex items-center justify-end">
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2">
                            <div className="h-5 w-5 rounded border-2 border-green-500 bg-green-500/20" />
                            <span className="text-xs font-medium">
                                Answered ({answeredCount})
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="h-5 w-5 rounded border-2 border-muted-foreground" />
                            <span className="text-xs font-medium">
                                Not Answered ({notAnsweredCount})
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="h-5 w-5 rounded border-2 border-primary bg-primary/20" />
                            <span className="text-xs font-medium">Current</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="flex h-5 w-5 items-center justify-center rounded border-2 border-orange-500 bg-orange-500/20">
                                <Flag className="h-3 w-3 fill-orange-500 text-orange-500" />
                            </div>
                            <span className="text-xs font-medium">
                                Marked ({markedCount})
                            </span>
                        </div>
                    </div>
                </div>
                <Button onClick={onSubmit} size="lg">
                    Submit Exam
                </Button>
            </div>
        </div>
    );
}
