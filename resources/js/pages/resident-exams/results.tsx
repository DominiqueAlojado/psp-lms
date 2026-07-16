import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Award,
    CheckCircle2,
    Clock,
    FileText,
    Lightbulb,
    Target,
    TrendingUp,
    XCircle,
} from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My Exams',
        href: '/resident-exams',
    },
    {
        title: 'Results',
        href: '#',
    },
];

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

interface PageProps {
    exam: Exam;
    attempt: Attempt;
    [key: string]: unknown;
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

export default function ExamResults() {
    const { exam, attempt } = usePage<PageProps>().props;
    const [reviewFilter, setReviewFilter] = useState<ReviewFilter>('all');

    const correctCount = exam.questions.filter((question) => question.is_correct).length;
    const incorrectCount = exam.questions.length - correctCount;
    const passed = attempt.score >= exam.passing_score;
    const passingPercent = (exam.passing_score / exam.total_points) * 100;
    const scoreDelta = Number((attempt.score - exam.passing_score).toFixed(2));
    const timeDisplay = formatDuration(attempt.time_taken_minutes);

    const filteredQuestions = useMemo(() => {
        return exam.questions.filter((question) => {
            if (reviewFilter === 'incorrect') {
                return !question.is_correct;
            }

            if (reviewFilter === 'with-explanation') {
                return Boolean(question.explanation);
            }

            return true;
        });
    }, [exam.questions, reviewFilter]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Results - ${exam.title}`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <HeadingSmall
                        title="Exam Results"
                        description="Review your performance and learn from your answers"
                    />
                    <Button asChild variant="outline">
                        <Link href="/resident-exams">Back to Exams</Link>
                    </Button>
                </div>

                <Card
                    className={cn(
                        'border shadow-sm',
                        passed
                            ? 'border-green-200/80 bg-green-50/60 dark:border-green-900/50 dark:bg-green-900/20'
                            : 'border-amber-200/80 bg-amber-50/70 dark:border-amber-900/50 dark:bg-amber-900/20',
                    )}
                >
                    <CardHeader className="space-y-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div className="flex-1 space-y-2">
                                <CardTitle className="text-2xl">
                                    {exam.title}
                                </CardTitle>
                                {exam.description && (
                                    <p className="text-sm text-muted-foreground">
                                        {exam.description}
                                    </p>
                                )}
                                <p className="text-sm text-muted-foreground">
                                    {passed
                                        ? `You cleared the passing mark by ${Math.abs(scoreDelta)} point${Math.abs(scoreDelta) === 1 ? '' : 's'}.`
                                        : `You are ${Math.abs(scoreDelta)} point${Math.abs(scoreDelta) === 1 ? '' : 's'} below the passing mark.`}
                                </p>
                            </div>
                            <Badge
                                variant={passed ? 'default' : 'secondary'}
                                className={cn(
                                    'self-start rounded-full px-3 py-1 text-base',
                                    passed
                                        ? 'bg-green-600 text-white'
                                        : 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
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
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div className="flex items-center gap-3 rounded-xl border bg-background/95 p-4">
                                <div
                                    className={cn(
                                        'flex h-12 w-12 items-center justify-center rounded-full',
                                        passed
                                            ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
                                    )}
                                >
                                    <TrendingUp className="h-6 w-6" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">
                                        {attempt.percentage.toFixed(2)}%
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Final score
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 rounded-xl border bg-background/95 p-4">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                    <Target className="h-6 w-6" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">
                                        {attempt.score}/{exam.total_points}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Points
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 rounded-xl border bg-background/95 p-4">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                    <FileText className="h-6 w-6" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">
                                        {correctCount}/{exam.questions.length}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Correct answers
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 rounded-xl border bg-background/95 p-4">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                                    <Clock className="h-6 w-6" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">
                                        {timeDisplay.value}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {timeDisplay.label}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                            <div className="rounded-full border bg-background/90 px-3 py-1.5">
                                Passing mark: {exam.passing_score}/{exam.total_points} (
                                {passingPercent.toFixed(0)}%)
                            </div>
                            <div className="rounded-full border bg-background/90 px-3 py-1.5">
                                Incorrect: {incorrectCount}
                            </div>
                            <div className="rounded-full border bg-background/90 px-3 py-1.5">
                                Submitted: {attempt.submitted_at}
                            </div>
                            <div className="rounded-full border bg-background/90 px-3 py-1.5">
                                Started: {attempt.started_at}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="space-y-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Detailed Question Breakdown
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Filter the review to focus on missed items or explanations.
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
                        <Card>
                            <CardContent className="p-6 text-center text-sm text-muted-foreground">
                                No questions match the current filter.
                            </CardContent>
                        </Card>
                    )}

                    {filteredQuestions.map((question) => {
                        const questionIndex = exam.questions.findIndex(
                            (item) => item.id === question.id,
                        );

                        return (
                            <Card key={question.id}>
                                <CardContent className="p-6">
                                    <div className="space-y-4">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge variant="secondary" className="rounded-full">
                                                Question {questionIndex + 1}
                                            </Badge>
                                            <Badge
                                                variant={question.is_correct ? 'outline' : 'destructive'}
                                                className={cn(
                                                    question.is_correct &&
                                                        'border-green-600 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400',
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
                                                        Incorrect
                                                    </>
                                                )}
                                            </Badge>
                                            <Badge variant="outline" className="rounded-full">
                                                {question.points_earned}/{question.points} pts
                                            </Badge>
                                        </div>

                                        <div className="flex items-start gap-4">
                                            <div
                                                className={cn(
                                                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-base font-medium',
                                                    question.is_correct
                                                        ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                                        : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
                                                )}
                                            >
                                                {questionIndex + 1}
                                            </div>
                                            <div className="flex-1">
                                                <div
                                                    className="prose prose-base dark:prose-invert max-w-none"
                                                    dangerouslySetInnerHTML={{
                                                        __html: question.question_text,
                                                    }}
                                                />

                                                {question.image_url && (
                                                    <img
                                                        src={question.image_url}
                                                        alt="Question"
                                                        className="mt-4 max-w-md rounded-lg border"
                                                    />
                                                )}
                                            </div>
                                        </div>

                                        <Separator />

                                        <div className="space-y-2">
                                            <p className="text-sm font-medium text-muted-foreground">
                                                Answer Choices
                                            </p>
                                            {question.choices.map((choice) => {
                                                const isSelected =
                                                    question.selected_choice_ids.includes(
                                                        choice.id,
                                                    );
                                                const isCorrect = choice.is_correct;

                                                return (
                                                    <div
                                                        key={choice.id}
                                                        className={cn(
                                                            'rounded-lg border-2 p-4 transition-colors',
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
                                                        <div className="flex items-start gap-3">
                                                            <div className="flex-1">
                                                                <div
                                                                    className="prose prose-sm dark:prose-invert max-w-none"
                                                                    dangerouslySetInnerHTML={{
                                                                        __html: choice.choice_text,
                                                                    }}
                                                                />
                                                            </div>
                                                            <div className="flex shrink-0 flex-wrap gap-2">
                                                                {isCorrect && (
                                                                    <Badge
                                                                        variant="outline"
                                                                        className="border-green-600 bg-green-50 text-green-600 dark:bg-green-900/20"
                                                                    >
                                                                        <CheckCircle2 className="mr-1 h-3 w-3" />
                                                                        Correct answer
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
                                                <Separator />
                                                <div className="rounded-lg border bg-blue-50/50 p-4 dark:bg-blue-900/10">
                                                    <p className="mb-2 text-sm font-semibold text-blue-900 dark:text-blue-200">
                                                        <span className="inline-flex items-center gap-1.5">
                                                            <Lightbulb className="h-4 w-4" />
                                                            Explanation
                                                        </span>
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
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                <div className="flex flex-col justify-center gap-3 pb-6 sm:flex-row">
                    <Button asChild variant="outline" size="lg">
                        <Link href="/resident-exams">Back to Exams</Link>
                    </Button>
                    {!passed && (
                        <Button asChild size="lg">
                            <Link href={`/exams/${exam.type}/${exam.id}/take`}>
                                Retake Exam
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
