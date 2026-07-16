import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Flag, X } from 'lucide-react';
import { memo, MutableRefObject } from 'react';

interface Question {
    id: number;
}

interface QuestionPaletteSidebarProps {
    isOpen: boolean;
    onClose: () => void;
    questions: Question[];
    currentQuestionIndex: number;
    markedForReview: Set<number>;
    answeredQuestionIds: Set<number>;
    onQuestionClick: (index: number) => void;
    questionRefs: MutableRefObject<(HTMLButtonElement | null)[]>;
}

export const QuestionPaletteSidebar = memo(function QuestionPaletteSidebar({
    isOpen,
    onClose,
    questions,
    currentQuestionIndex,
    markedForReview,
    answeredQuestionIds,
    onQuestionClick,
    questionRefs,
}: QuestionPaletteSidebarProps) {
    return (
        <div
            className={cn(
                'fixed inset-y-0 left-0 z-40 w-80 transform border-r bg-background transition-transform duration-300 lg:relative lg:translate-x-0',
                isOpen ? 'translate-x-0' : '-translate-x-full',
            )}
        >
            <div className="flex h-full flex-col">
                {/* Sidebar Header */}
                <div className="border-b p-4">
                    <div className="flex items-center justify-between">
                        <h2 className="font-semibold">Question Palette</h2>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="lg:hidden"
                            onClick={onClose}
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                {/* Question List */}
                <div className="flex-1 overflow-y-auto p-4">
                    <div className="space-y-2">
                        {questions.map((question, index) => {
                            const answered = answeredQuestionIds.has(
                                question.id,
                            );
                            const current = index === currentQuestionIndex;
                            const marked = markedForReview.has(question.id);

                            return (
                                <button
                                    key={question.id}
                                    ref={(el) => {
                                        if (questionRefs.current) {
                                            questionRefs.current[index] = el;
                                        }
                                    }}
                                    onClick={() => onQuestionClick(index)}
                                    className={cn(
                                        'flex w-full items-center gap-3 rounded-lg border-2 px-4 py-2.5 text-left transition-all',
                                        current &&
                                            'border-[var(--exam-current-border)] bg-[var(--exam-current-bg)] ring-2 ring-[var(--exam-current-border)] ring-offset-2',
                                        answered &&
                                            !current &&
                                            'border-[var(--exam-answered-border)] bg-[var(--exam-answered-bg)] hover:bg-[color-mix(in_oklab,var(--exam-answered-bg)_82%,white)]',
                                        !answered &&
                                            !current &&
                                            'border-[var(--exam-neutral-border)] bg-[var(--exam-neutral-bg)] hover:border-[var(--exam-current-border)] hover:bg-muted',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-sm font-semibold',
                                            current &&
                                                'bg-[var(--exam-current-border)] text-[var(--exam-current-fg)]',
                                            answered &&
                                                !current &&
                                                'bg-[var(--exam-answered-border)] text-[var(--exam-current-fg)]',
                                            !answered &&
                                                !current &&
                                                'bg-muted text-[var(--exam-neutral-fg)]',
                                        )}
                                    >
                                        {index + 1}
                                    </span>
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2">
                                            <p className="text-sm font-medium">
                                                Item No. {index + 1}
                                            </p>
                                            {marked && (
                                                <Flag className="h-3.5 w-3.5 fill-[var(--exam-marked-fg)] text-[var(--exam-marked-fg)]" />
                                            )}
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {answered
                                                ? 'Answered'
                                                : 'Not answered'}
                                        </p>
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                </div>
            </div>
        </div>
    );
});
