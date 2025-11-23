import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import {
    Award,
    CheckCircle2,
    Clock,
    FileText,
    Loader2,
    Play,
    Target,
    TrendingUp,
    XCircle,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

interface Choice {
    id: number;
    choice_text: string;
    is_correct: boolean;
    order: number;
}

interface Question {
    id: number;
    question_type: 'multiple_choice' | 'multiple_select' | 'true_false';
    question_text: string;
    points: number;
    explanation: string | null;
    image_url: string | null;
    order: number;
    choices: Choice[];
    selected_choice_ids: number[];
    is_correct: boolean;
    points_earned: number;
}

interface Exam {
    id: number;
    title: string;
    description: string | null;
    type: 'institution' | 'inservice';
    total_points: number;
    passing_score: number;
    questions: Question[];
}

interface Attempt {
    id: number;
    score: number;
    percentage: number;
    started_at: string;
    submitted_at: string;
    time_taken_minutes: number | null;
}

interface ResultsData {
    exam: Exam;
    attempt: Attempt;
}

interface ExamResultsSheetProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    examId: number;
    examType: 'institution' | 'inservice';
    examTitle: string;
    onRetake?: () => void;
}

export function ExamResultsSheet({
    open,
    onOpenChange,
    examId,
    examType,
    examTitle,
    onRetake,
}: ExamResultsSheetProps) {
    const [loading, setLoading] = useState(false);
    const [data, setData] = useState<ResultsData | null>(null);
    const [error, setError] = useState<string | null>(null);

    const fetchResults = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(
                `/exams/${examType}/${examId}/results-data`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                },
            );

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error('API Error:', response.status, errorData);
                throw new Error(errorData.error || 'Failed to load results');
            }

            const data = await response.json();
            setData(data);
        } catch (err) {
            setError('Failed to load exam results. Please try again.');
            console.error('Fetch error:', err);
        } finally {
            setLoading(false);
        }
    }, [examId, examType]);

    // Always fetch fresh data when dialog opens or exam changes
    useEffect(() => {
        if (open) {
            setData(null); // Clear old data first
            fetchResults(); // Fetch fresh data
        }
    }, [open, fetchResults]);

    const handleRetake = () => {
        onOpenChange(false);
        if (onRetake) {
            onRetake();
        }
    };

    if (!data && !loading && !error) {
        return null;
    }

    const correctCount =
        data?.exam.questions.filter((q) => q.is_correct).length || 0;
    const passed =
        (data?.attempt.percentage || 0) >= (data?.exam.passing_score || 0);

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="w-full overflow-y-auto sm:max-w-2xl lg:max-w-4xl"
            >
                <SheetHeader className="border-b pb-4">
                    <SheetTitle className="text-2xl">{examTitle}</SheetTitle>
                    <SheetDescription className="sr-only">
                        Detailed exam results including score, questions, and
                        answers
                    </SheetDescription>
                </SheetHeader>

                <div className="mt-0 space-y-6 p-4">
                    {loading && (
                        <div className="flex items-center justify-center py-12">
                            <Loader2 className="h-8 w-8 animate-spin text-primary" />
                        </div>
                    )}

                    {error && (
                        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-center text-red-900 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-200">
                            {error}
                        </div>
                    )}

                    {data && (
                        <>
                            {/* Summary Card */}
                            <div
                                className={cn(
                                    'rounded-lg border-2 p-6',
                                    passed
                                        ? 'border-green-200 bg-green-50/50 dark:border-green-900/50 dark:bg-green-900/20'
                                        : 'border-amber-200 bg-amber-50/50 dark:border-amber-900/50 dark:bg-amber-900/20',
                                )}
                            >
                                <div className="mb-4 flex items-center justify-between">
                                    <h3 className="text-lg font-semibold">
                                        Your Results
                                    </h3>
                                    <Badge
                                        variant={
                                            passed ? 'default' : 'secondary'
                                        }
                                        className={cn(
                                            'text-base',
                                            passed && 'bg-green-600',
                                        )}
                                    >
                                        {passed ? (
                                            <>
                                                <Award className="mr-2 h-4 w-4" />
                                                Passed
                                            </>
                                        ) : (
                                            'Not Passed'
                                        )}
                                    </Badge>
                                </div>

                                <div className="grid gap-4 md:grid-cols-4">
                                    {/* Score */}
                                    <div className="flex items-center gap-3 rounded-lg border bg-background p-4">
                                        <div
                                            className={cn(
                                                'flex h-10 w-10 items-center justify-center rounded-full',
                                                passed
                                                    ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                                    : 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
                                            )}
                                        >
                                            <TrendingUp className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-xl font-bold">
                                                {data.attempt.percentage.toFixed(
                                                    2,
                                                )}
                                                %
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Score
                                            </p>
                                        </div>
                                    </div>

                                    {/* Points */}
                                    <div className="flex items-center gap-3 rounded-lg border bg-background p-4">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                            <Target className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-xl font-bold">
                                                {data.attempt.score}/
                                                {data.exam.total_points}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Points
                                            </p>
                                        </div>
                                    </div>

                                    {/* Correct/Incorrect */}
                                    <div className="flex items-center gap-3 rounded-lg border bg-background p-4">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                            <FileText className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-xl font-bold">
                                                {correctCount}/
                                                {data.exam.questions.length}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Correct
                                            </p>
                                        </div>
                                    </div>

                                    {/* Time */}
                                    <div className="flex items-center gap-3 rounded-lg border bg-background p-4">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                                            <Clock className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-xl font-bold">
                                                {data.attempt
                                                    .time_taken_minutes ||
                                                    'N/A'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {data.attempt.time_taken_minutes
                                                    ? 'Mins'
                                                    : 'Time'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Question Breakdown */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold">
                                    Question Breakdown
                                </h3>

                                {data.exam.questions.map((question, index) => (
                                    <div
                                        key={question.id}
                                        className="rounded-lg border p-4"
                                    >
                                        <div className="space-y-4">
                                            {/* Question Header */}
                                            <Badge
                                                variant={
                                                    question.is_correct
                                                        ? 'default'
                                                        : 'destructive'
                                                }
                                                className="shrink-0"
                                            >
                                                {question.is_correct ? (
                                                    <>
                                                        <CheckCircle2 className="mr-1 h-3 w-3" />
                                                        Correct
                                                    </>
                                                ) : (
                                                    <>
                                                        <XCircle className="mr-1 h-3 w-3" />
                                                        Wrong
                                                    </>
                                                )}
                                            </Badge>
                                            <div className="flex items-start gap-3">
                                                <div
                                                    className={cn(
                                                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-medium',
                                                        question.is_correct
                                                            ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                                            : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
                                                    )}
                                                >
                                                    {index + 1}
                                                </div>
                                                <div className="flex-1">
                                                    <div className="flex items-start justify-between gap-2">
                                                        <div
                                                            className="prose prose-sm dark:prose-invert max-w-none flex-1"
                                                            dangerouslySetInnerHTML={{
                                                                __html: question.question_text,
                                                            }}
                                                        />
                                                    </div>

                                                    {question.image_url && (
                                                        <img
                                                            src={
                                                                question.image_url
                                                            }
                                                            alt="Question"
                                                            className="mt-4 max-w-md rounded-lg border"
                                                        />
                                                    )}
                                                </div>
                                            </div>
                                            <Separator />
                                            {/* Choices */}
                                            <div className="space-y-2">
                                                {question.choices.map(
                                                    (choice) => {
                                                        const isSelected =
                                                            question.selected_choice_ids.includes(
                                                                choice.id,
                                                            );
                                                        const isCorrect =
                                                            choice.is_correct;

                                                        return (
                                                            <div
                                                                key={choice.id}
                                                                className={cn(
                                                                    'rounded-lg border-2 p-3',
                                                                    isCorrect &&
                                                                        'border-green-200 bg-green-50/50 dark:border-green-900/50 dark:bg-green-900/20',
                                                                    isSelected &&
                                                                        !isCorrect &&
                                                                        'border-red-200 bg-red-50/50 dark:border-red-900/50 dark:bg-red-900/20',
                                                                    !isSelected &&
                                                                        !isCorrect &&
                                                                        'border-border',
                                                                )}
                                                            >
                                                                <div className="flex items-start gap-2">
                                                                    <div
                                                                        className="prose prose-sm dark:prose-invert max-w-none flex-1"
                                                                        dangerouslySetInnerHTML={{
                                                                            __html: choice.choice_text,
                                                                        }}
                                                                    />
                                                                    <div className="flex shrink-0 gap-1">
                                                                        {isCorrect && (
                                                                            <Badge
                                                                                variant="outline"
                                                                                className="border-green-600 bg-green-50 text-green-600 dark:bg-green-900/20"
                                                                            >
                                                                                ✓
                                                                            </Badge>
                                                                        )}
                                                                        {isSelected && (
                                                                            <Badge
                                                                                variant="outline"
                                                                                className={cn(
                                                                                    isCorrect
                                                                                        ? 'border-green-600 bg-green-50 text-green-600 dark:bg-green-900/20'
                                                                                        : 'border-red-600 bg-red-50 text-red-600 dark:bg-red-900/20',
                                                                                )}
                                                                            >
                                                                                You
                                                                            </Badge>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        );
                                                    },
                                                )}
                                            </div>
                                            {/* Explanation */}
                                            {question.explanation && (
                                                <>
                                                    <Separator />
                                                    <div className="rounded-lg border bg-blue-50/50 p-3 dark:bg-blue-900/10">
                                                        <p className="mb-1 text-xs font-semibold text-blue-900 dark:text-blue-200">
                                                            💡 Explanation
                                                        </p>
                                                        <div
                                                            className="prose prose-sm dark:prose-invert max-w-none"
                                                            dangerouslySetInnerHTML={{
                                                                __html: question.explanation,
                                                            }}
                                                        />
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Actions */}
                            <div className="sticky bottom-2 flex justify-end gap-2 border-t bg-background pt-4">
                                <Button
                                    variant="outline"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Close
                                </Button>
                                {!passed && onRetake && (
                                    <Button onClick={handleRetake}>
                                        <Play className="mr-2 h-4 w-4" />
                                        Retake Exam
                                    </Button>
                                )}
                            </div>
                        </>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}
