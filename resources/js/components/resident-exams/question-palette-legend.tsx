import { ExamTimer } from '@/components/resident-exams/exam-timer';
import { Button } from '@/components/ui/button';
import { Flag } from 'lucide-react';
import { memo } from 'react';

interface QuestionPaletteLegendProps {
    answeredCount: number;
    notAnsweredCount: number;
    markedCount: number;
    durationMinutes: number | null;
    startedAt: string;
    onSubmit: () => void;
    onTimeExpired: () => void;
    isSaving?: boolean;
}

export const QuestionPaletteLegend = memo(function QuestionPaletteLegend({
    answeredCount,
    notAnsweredCount,
    markedCount,
    durationMinutes,
    startedAt,
    onSubmit,
    onTimeExpired,
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
                    {durationMinutes && (
                        <ExamTimer
                            durationMinutes={durationMinutes}
                            startedAt={startedAt}
                            onExpire={onTimeExpired}
                            className="gap-1.5 px-2.5 py-1.5 sm:gap-2 sm:px-3 sm:py-2 [&_svg]:h-3.5 [&_svg]:w-3.5 [&_span]:text-sm [&_span]:sm:text-base"
                        />
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
                        <div className="h-4 w-4 rounded border-2 border-[var(--exam-answered-border)] bg-[var(--exam-answered-bg)]" />
                        <span className="text-xs font-medium">
                            Ans ({answeredCount})
                        </span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="h-4 w-4 rounded border-2 border-[var(--exam-neutral-border)] bg-[var(--exam-neutral-bg)]" />
                        <span className="text-xs font-medium">
                            Not ({notAnsweredCount})
                        </span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="h-4 w-4 rounded border-2 border-[var(--exam-current-border)] bg-[var(--exam-current-bg)]" />
                        <span className="text-xs font-medium">Current</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="flex h-4 w-4 items-center justify-center rounded border-2 border-[var(--exam-marked-border)] bg-[var(--exam-marked-bg)]">
                            <Flag className="h-2.5 w-2.5 fill-[var(--exam-marked-fg)] text-[var(--exam-marked-fg)]" />
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
                {durationMinutes && (
                    <ExamTimer
                        durationMinutes={durationMinutes}
                        startedAt={startedAt}
                        onExpire={onTimeExpired}
                    />
                )}
                <div className="flex items-center justify-end">
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2">
                            <div className="h-5 w-5 rounded border-2 border-[var(--exam-answered-border)] bg-[var(--exam-answered-bg)]" />
                            <span className="text-xs font-medium">
                                Answered ({answeredCount})
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="h-5 w-5 rounded border-2 border-[var(--exam-neutral-border)] bg-[var(--exam-neutral-bg)]" />
                            <span className="text-xs font-medium">
                                Not Answered ({notAnsweredCount})
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="h-5 w-5 rounded border-2 border-[var(--exam-current-border)] bg-[var(--exam-current-bg)]" />
                            <span className="text-xs font-medium">Current</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="flex h-5 w-5 items-center justify-center rounded border-2 border-[var(--exam-marked-border)] bg-[var(--exam-marked-bg)]">
                                <Flag className="h-3 w-3 fill-[var(--exam-marked-fg)] text-[var(--exam-marked-fg)]" />
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
});
