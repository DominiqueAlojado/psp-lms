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
    Target,
    TrendingUp,
    XCircle,
} from 'lucide-react';

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

export default function ExamResults() {
    const { exam, attempt } = usePage<PageProps>().props;

    const correctCount = exam.questions.filter((q) => q.is_correct).length;
    const incorrectCount = exam.questions.length - correctCount;
    const passed = attempt.score >= exam.passing_score;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Results - ${exam.title}`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Exam Results"
                        description="Review your performance and learn from your answers"
                    />
                    <Button asChild variant="outline">
                        <Link href="/resident-exams">Back to Exams</Link>
                    </Button>
                </div>

                {/* Summary Card */}
                <Card
                    className={cn(
                        'border-2',
                        passed
                            ? 'border-green-200 bg-green-50/50 dark:border-green-900/50 dark:bg-green-900/20'
                            : 'border-amber-200 bg-amber-50/50 dark:border-amber-900/50 dark:bg-amber-900/20',
                    )}
                >
                    <CardHeader>
                        <div className="flex items-start justify-between">
                            <div className="flex-1">
                                <CardTitle className="text-2xl">
                                    {exam.title}
                                </CardTitle>
                                {exam.description && (
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {exam.description}
                                    </p>
                                )}
                            </div>
                            <Badge
                                variant={passed ? 'default' : 'secondary'}
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
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            {/* Score */}
                            <div className="flex items-center gap-3 rounded-lg border bg-background p-4">
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
                                        {attempt.percentage}%
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Your Score
                                    </p>
                                </div>
                            </div>

                            {/* Points */}
                            <div className="flex items-center gap-3 rounded-lg border bg-background p-4">
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

                            {/* Correct/Incorrect */}
                            <div className="flex items-center gap-3 rounded-lg border bg-background p-4">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                    <FileText className="h-6 w-6" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">
                                        {correctCount}/{exam.questions.length}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Correct
                                    </p>
                                </div>
                            </div>

                            {/* Time */}
                            <div className="flex items-center gap-3 rounded-lg border bg-background p-4">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                                    <Clock className="h-6 w-6" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">
                                        {attempt.time_taken_minutes || 'N/A'}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {attempt.time_taken_minutes
                                            ? 'Minutes'
                                            : 'Time'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Additional Info */}
                        <div className="mt-4 flex flex-wrap gap-4 text-sm text-muted-foreground">
                            <div>
                                <span className="font-medium">Submitted: </span>
                                {attempt.submitted_at}
                            </div>
                            <div>
                                <span className="font-medium">
                                    MPL:{' '}
                                </span>
                                {exam.passing_score}
                            </div>
                            <div>
                                <span className="font-medium">Incorrect: </span>
                                {incorrectCount}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Question-by-Question Breakdown */}
                <div className="space-y-4">
                    <h2 className="text-lg font-semibold">
                        Detailed Question Breakdown
                    </h2>

                    {exam.questions.map((question, index) => (
                        <Card key={question.id}>
                            <CardContent className="p-6">
                                <div className="space-y-4">
                                    {/* Question Header */}
                                    <div className="flex items-start gap-4">
                                        <div
                                            className={cn(
                                                'flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-base font-medium',
                                                question.is_correct
                                                    ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                                    : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
                                            )}
                                        >
                                            {index + 1}
                                        </div>
                                        <div className="flex-1">
                                            <div className="flex items-start justify-between gap-4">
                                                <div
                                                    className="prose prose-base dark:prose-invert max-w-none"
                                                    dangerouslySetInnerHTML={{
                                                        __html: question.question_text,
                                                    }}
                                                />
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
                                                            Incorrect
                                                        </>
                                                    )}
                                                </Badge>
                                            </div>

                                            {question.image_url && (
                                                <img
                                                    src={question.image_url}
                                                    alt="Question"
                                                    className="mt-4 max-w-md rounded-lg border"
                                                />
                                            )}

                                            <div className="mt-2 text-sm text-muted-foreground">
                                                Points: {question.points_earned}
                                                /{question.points}
                                            </div>
                                        </div>
                                    </div>

                                    <Separator />

                                    {/* Answer Choices */}
                                    <div className="space-y-2">
                                        <p className="text-sm font-medium text-muted-foreground">
                                            Answer Choices:
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
                                                        <div className="flex shrink-0 gap-2">
                                                            {isCorrect && (
                                                                <Badge
                                                                    variant="outline"
                                                                    className="border-green-600 bg-green-50 text-green-600 dark:bg-green-900/20"
                                                                >
                                                                    <CheckCircle2 className="mr-1 h-3 w-3" />
                                                                    Correct
                                                                    Answer
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
                                                                    Your Answer
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {/* Explanation */}
                                    {question.explanation && (
                                        <>
                                            <Separator />
                                            <div className="rounded-lg border bg-blue-50/50 p-4 dark:bg-blue-900/10">
                                                <p className="mb-2 text-sm font-semibold text-blue-900 dark:text-blue-200">
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
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Bottom Actions */}
                <div className="flex justify-center gap-4 pb-6">
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
