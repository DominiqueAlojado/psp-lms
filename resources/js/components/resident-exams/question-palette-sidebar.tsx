import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Flag, X } from 'lucide-react';
import { MutableRefObject } from 'react';

interface Question {
    id: number;
}

interface QuestionPaletteSidebarProps {
    isOpen: boolean;
    onClose: () => void;
    questions: Question[];
    currentQuestionIndex: number;
    markedForReview: Set<number>;
    onQuestionClick: (index: number) => void;
    isQuestionAnswered: (questionId: number) => boolean;
    questionRefs: MutableRefObject<(HTMLButtonElement | null)[]>;
}

export function QuestionPaletteSidebar({
    isOpen,
    onClose,
    questions,
    currentQuestionIndex,
    markedForReview,
    onQuestionClick,
    isQuestionAnswered,
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
                            const answered = isQuestionAnswered(question.id);
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
                                            'border-primary bg-primary/20 ring-2 ring-primary ring-offset-2',
                                        answered &&
                                            !current &&
                                            'border-green-500 bg-green-500/20 hover:bg-green-500/30',
                                        !answered &&
                                            !current &&
                                            'border-muted-foreground hover:border-primary hover:bg-muted',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-sm font-semibold',
                                            current &&
                                                'bg-primary text-primary-foreground',
                                            answered &&
                                                !current &&
                                                'bg-green-500 text-white',
                                            !answered && !current && 'bg-muted',
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
                                                <Flag className="h-3.5 w-3.5 fill-orange-500 text-orange-500" />
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
}
