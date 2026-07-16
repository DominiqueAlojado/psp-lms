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
import { cn, preserveOrgParam } from '@/lib/utils';
import type { SharedData } from '@/types';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import {
    Award,
    CheckCircle2,
    Clock,
    FileText,
    Lightbulb,
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

type ReviewFilter = 'all' | 'incorrect' | 'with-explanation';

function formatDuration(minutes: number | null) {
    if (minutes === null || Number.isNaN(Number(minutes))) {
        return { value: 'N/A', label: 'Time' };
    }

    const totalMinutes = Number(minutes);
    const wholeMinutes = Math.floor(totalMinutes);
    const seconds = Math.round((totalMinutes - wholeMinutes) * 60);

    if (seconds > 0) {
        return {
            value: `${wholeMinutes}m ${seconds.toString().padStart(2, '0')}s`,
            label: 'Time spent',
        };
    }

    return {
        value: `${wholeMinutes} min`,
        label: 'Time spent',
    };
}

export function ExamResultsSheet({
    open,
    onOpenChange,
    examId,
    examType,
    examTitle,
    onRetake,
}: ExamResultsSheetProps) {
    const { auth } = usePage<SharedData>().props;
    const [loading, setLoading] = useState(false);
    const [data, setData] = useState<ResultsData | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [reviewFilter, setReviewFilter] = useState<ReviewFilter>('all');

    const fetchResults = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const resultsUrl = preserveOrgParam(
                `/exams/${examType}/${examId}/results-data`,
                auth.currentOrganization?.slug,
            );
            const response = await axios.get<ResultsData>(
                typeof resultsUrl === 'string' ? resultsUrl : resultsUrl.url,
                {
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );

            setData(response.data);
        } catch (err) {
            if (axios.isAxiosError(err)) {
                const status = err.response?.status;
                const responseData = err.response?.data as
                    | { error?: string; message?: string }
                    | undefined;
                const message =
                    responseData?.error || responseData?.message || null;

                if (status === 401 || status === 419) {
                    window.location.href = '/login';
                    return;
                }

                setError(
                    message || 'Failed to load exam results. Please try again.',
                );
                console.error('Exam results API error:', {
                    status,
                    data: responseData,
                    message: err.message,
                });
            } else {
                setError('Failed to load exam results. Please try again.');
                console.error('Exam results fetch error:', err);
            }
        } finally {
            setLoading(false);
        }
    }, [auth.currentOrganization?.slug, examId, examType]);

    useEffect(() => {
        if (open) {
            setData(null);
            setReviewFilter('all');
            fetchResults();
        }
    }, [open, fetchResults]);

    const handleRetake = () => {
        onOpenChange(false);
        onRetake?.();
    };

    if (!data && !loading && !error) {
        return null;
    }

    const correctCount =
        data?.exam.questions.filter((question) => question.is_correct).length ||
        0;
    const incorrectCount = (data?.exam.questions.length || 0) - correctCount;
    const passed =
        (data?.attempt.score || 0) >= (data?.exam.passing_score || 0);
    const passingPercent = data
        ? (data.exam.passing_score / data.exam.total_points) * 100
        : 0;
    const scoreDelta = data
        ? Number((data.attempt.score - data.exam.passing_score).toFixed(2))
        : 0;
    const timeDisplay = formatDuration(data?.attempt.time_taken_minutes ?? null);
    const filteredQuestions =
        data?.exam.questions.filter((question) => {
            if (reviewFilter === 'incorrect') {
                return !question.is_correct;
            }

            if (reviewFilter === 'with-explanation') {
                return Boolean(question.explanation);
            }

            return true;
        }) || [];

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="w-full overflow-y-auto sm:max-w-2xl lg:max-w-4xl"
            >
                <SheetHeader className="border-b px-4 pt-4 pb-3 sm:px-6 sm:pb-4">
                    <SheetTitle className="text-lg sm:text-xl lg:text-2xl">
                        {examTitle}
                    </SheetTitle>
                    <SheetDescription className="sr-only">
                        Detailed exam results including score, questions, and
                        answers
                    </SheetDescription>
                </SheetHeader>

                <div className="mt-0 space-y-4 px-4 pb-20 sm:space-y-6 sm:px-6 sm:pb-4">
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
                            <div
                                className={cn(
                                    'rounded-2xl border p-4 shadow-sm sm:p-6',
                                    passed
                                        ? 'border-green-200/80 bg-green-50/60 dark:border-green-900/50 dark:bg-green-900/20'
                                        : 'border-amber-200/80 bg-amber-50/70 dark:border-amber-900/50 dark:bg-amber-900/20',
                                )}
                            >
                                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="space-y-1">
                                        <h3 className="text-base font-semibold sm:text-lg">
                                            Your Results
                                        </h3>
                                        <p className="text-sm text-muted-foreground">
                                            {passed
                                                ? `You cleared the passing mark by ${Math.abs(scoreDelta)} point${Math.abs(scoreDelta) === 1 ? '' : 's'}.`
                                                : `You are ${Math.abs(scoreDelta)} point${Math.abs(scoreDelta) === 1 ? '' : 's'} below the passing mark.`}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={passed ? 'default' : 'secondary'}
                                        className={cn(
                                            'self-start rounded-full px-3 py-1 text-sm sm:text-base',
                                            passed
                                                ? 'bg-green-600 text-white'
                                                : 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
                                        )}
                                    >
                                        {passed ? (
                                            <>
                                                <Award className="mr-2 h-3 w-3 sm:h-4 sm:w-4" />
                                                Passed
                                            </>
                                        ) : (
                                            'Not Passed'
                                        )}
                                    </Badge>
                                </div>

                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                                    <div className="flex items-center gap-2 rounded-xl border bg-background/95 p-3 sm:gap-3 sm:p-4">
                                        <div
                                            className={cn(
                                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full sm:h-10 sm:w-10',
                                                passed
                                                    ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                                    : 'bg-warning-soft text-warning',
                                            )}
                                        >
                                            <TrendingUp className="h-4 w-4 sm:h-5 sm:w-5" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-lg font-bold sm:text-xl">
                                                {data.attempt.percentage.toFixed(2)}%
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Final score
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 rounded-xl border bg-background/95 p-3 sm:gap-3 sm:p-4">
                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 sm:h-10 sm:w-10 dark:bg-blue-900/30 dark:text-blue-400">
                                            <Target className="h-4 w-4 sm:h-5 sm:w-5" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-lg font-bold sm:text-xl">
                                                {data.attempt.score}/{data.exam.total_points}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Points
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 rounded-xl border bg-background/95 p-3 sm:gap-3 sm:p-4">
                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 text-purple-600 sm:h-10 sm:w-10 dark:bg-purple-900/30 dark:text-purple-400">
                                            <FileText className="h-4 w-4 sm:h-5 sm:w-5" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-lg font-bold sm:text-xl">
                                                {correctCount}/{data.exam.questions.length}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Correct answers
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 rounded-xl border bg-background/95 p-3 sm:gap-3 sm:p-4">
                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600 sm:h-10 sm:w-10 dark:bg-orange-900/30 dark:text-orange-400">
                                            <Clock className="h-4 w-4 sm:h-5 sm:w-5" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-lg font-bold sm:text-xl">
                                                {timeDisplay.value}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {timeDisplay.label}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-4 flex flex-wrap gap-2 text-xs text-muted-foreground sm:text-sm">
                                    <div className="rounded-full border bg-background/90 px-3 py-1.5">
                                        Passing mark: {data.exam.passing_score}/
                                        {data.exam.total_points} ({passingPercent.toFixed(0)}%)
                                    </div>
                                    <div className="rounded-full border bg-background/90 px-3 py-1.5">
                                        Incorrect: {incorrectCount}
                                    </div>
                                    <div className="rounded-full border bg-background/90 px-3 py-1.5">
                                        Reviewed items: {filteredQuestions.length}
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-3 sm:space-y-4">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 className="text-base font-semibold sm:text-lg">
                                            Question Breakdown
                                        </h3>
                                        <p className="text-sm text-muted-foreground">
                                            Review all items, only missed questions, or those with explanations.
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={reviewFilter === 'all' ? 'default' : 'outline'}
                                            onClick={() => setReviewFilter('all')}
                                        >
                                            All
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={reviewFilter === 'incorrect' ? 'default' : 'outline'}
                                            onClick={() => setReviewFilter('incorrect')}
                                        >
                                            Wrong only
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={reviewFilter === 'with-explanation' ? 'default' : 'outline'}
                                            onClick={() => setReviewFilter('with-explanation')}
                                        >
                                            With explanation
                                        </Button>
                                    </div>
                                </div>

                                {filteredQuestions.length === 0 && (
                                    <div className="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                                        No questions match the current filter.
                                    </div>
                                )}

                                {filteredQuestions.map((question) => {
                                    const questionIndex = data.exam.questions.findIndex(
                                        (item) => item.id === question.id,
                                    );

                                    return (
                                        <div
                                            key={question.id}
                                            className="rounded-xl border p-3 sm:p-4"
                                        >
                                            <div className="space-y-3 sm:space-y-4">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Badge variant="secondary" className="rounded-full">
                                                        Question {questionIndex + 1}
                                                    </Badge>
                                                    <Badge
                                                        variant={
                                                            question.is_correct
                                                                ? 'outline'
                                                                : 'destructive'
                                                        }
                                                        className={cn(
                                                            'shrink-0 text-xs sm:text-sm',
                                                            question.is_correct &&
                                                                'border-green-500 bg-green-50 text-green-700 dark:border-green-400 dark:bg-green-900/30 dark:text-green-400',
                                                        )}
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
                                                    <Badge
                                                        variant="outline"
                                                        className="rounded-full text-xs"
                                                    >
                                                        {question.points_earned}/{question.points} pts
                                                    </Badge>
                                                </div>

                                                <div className="flex items-start gap-2 sm:gap-3">
                                                    <div
                                                        className={cn(
                                                            'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-medium sm:h-7 sm:w-7 sm:text-sm',
                                                            question.is_correct
                                                                ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                                                : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
                                                        )}
                                                    >
                                                        {questionIndex + 1}
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <div
                                                            className="prose prose-xs sm:prose-sm dark:prose-invert max-w-none break-words"
                                                            dangerouslySetInnerHTML={{
                                                                __html: question.question_text,
                                                            }}
                                                        />
                                                        {question.image_url && (
                                                            <div className="mt-3 w-full sm:mt-4">
                                                                <img
                                                                    src={question.image_url}
                                                                    alt="Question"
                                                                    className="w-full max-w-full rounded-lg border bg-background/94 sm:max-w-md"
                                                                />
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>

                                                <Separator />

                                                <div className="space-y-2">
                                                    {question.choices.map((choice) => {
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
                                                                    'rounded-lg border-2 p-2.5 sm:p-3',
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
                                                                <div className="flex flex-col gap-2 sm:flex-row sm:items-start">
                                                                    <div
                                                                        className="prose prose-xs sm:prose-sm dark:prose-invert max-w-none flex-1 break-words"
                                                                        dangerouslySetInnerHTML={{
                                                                            __html: choice.choice_text,
                                                                        }}
                                                                    />
                                                                    <div className="flex shrink-0 flex-wrap gap-1.5 sm:flex-nowrap">
                                                                        {isCorrect && (
                                                                            <Badge
                                                                                variant="outline"
                                                                                className="border-green-600 bg-green-50 text-[10px] text-green-600 sm:text-xs dark:bg-green-900/20"
                                                                            >
                                                                                <CheckCircle2 className="mr-1 h-3 w-3" />
                                                                                Correct answer
                                                                            </Badge>
                                                                        )}
                                                                        {isSelected && (
                                                                            <Badge
                                                                                variant="outline"
                                                                                className={cn(
                                                                                    'text-[10px] sm:text-xs',
                                                                                    isCorrect
                                                                                        ? 'border-green-600 bg-green-50 text-green-600 dark:bg-green-900/20'
                                                                                        : 'border-red-600 bg-red-50 text-red-600 dark:bg-red-900/20',
                                                                                )}
                                                                            >
                                                                                Your answer
                                                                            </Badge>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        );
                                                    })}
                                                </div>

                                                {question.explanation && (
                                                    <>
                                                        <Separator className="my-3 sm:my-4" />
                                                        <div className="rounded-lg border bg-blue-50/50 p-2.5 sm:p-3 dark:bg-blue-900/10">
                                                            <p className="mb-1.5 text-xs font-semibold text-blue-900 sm:mb-1 dark:text-blue-200">
                                                                <span className="inline-flex items-center gap-1.5">
                                                                    <Lightbulb className="h-3.5 w-3.5" />
                                                                    Explanation
                                                                </span>
                                                            </p>
                                                            <div
                                                                className="prose prose-xs sm:prose-sm dark:prose-invert max-w-none break-words"
                                                                dangerouslySetInnerHTML={{
                                                                    __html: question.explanation,
                                                                }}
                                                            />
                                                        </div>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            <div className="sticky right-0 bottom-0 left-0 z-10 flex flex-col-reverse gap-2 border-t bg-background px-4 py-3 shadow-lg sm:relative sm:flex-row sm:justify-end sm:px-0 sm:py-4 sm:shadow-none">
                                <Button
                                    variant="outline"
                                    onClick={() => onOpenChange(false)}
                                    className="w-full sm:w-auto"
                                >
                                    Close
                                </Button>
                                {!passed && onRetake && (
                                    <Button
                                        onClick={handleRetake}
                                        className="w-full sm:w-auto"
                                    >
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
