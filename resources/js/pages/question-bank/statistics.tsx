import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, CheckCircle, TrendingUp } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Question Bank', href: '/question-bank' },
    { title: 'Statistics', href: '/question-bank/statistics' },
];

interface Question {
    id: number;
    question_text: string;
    question_type: string;
    points: number;
    topic: { id: number; name: string } | null;
    statistics: {
        times_answered: number;
        success_rate: number;
        average_time_seconds: number | null;
        computed_difficulty: string | null;
    };
}

interface TypeCount {
    question_type: string;
    count: number;
}

interface PageProps {
    totalQuestions: number;
    approvedQuestions: number;
    byType: TypeCount[];
    topPerforming: Question[];
    needsReview: Question[];
}

const typeLabels: Record<string, string> = {
    multiple_choice: 'Multiple Choice',
    multiple_select: 'Multiple Select',
    true_false: 'True/False',
};

const difficultyColors: Record<string, string> = {
    easy: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    hard: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

export default function QuestionBankStatistics({
    totalQuestions,
    approvedQuestions,
    byType,
    topPerforming,
    needsReview,
}: PageProps) {
    const approvalRate = totalQuestions > 0 ? ((approvedQuestions / totalQuestions) * 100).toFixed(0) : 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Question Bank Statistics" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Question Bank Statistics"
                        description="Analytics and insights about your question repository"
                    />
                    <Button variant="outline" onClick={() => router.visit('/question-bank')}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Back to Questions
                    </Button>
                </div>

                {/* Overview Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Questions</CardTitle>
                            <CheckCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalQuestions}</div>
                            <p className="text-xs text-muted-foreground">In your question bank</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Approved</CardTitle>
                            <TrendingUp className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{approvedQuestions}</div>
                            <p className="text-xs text-muted-foreground">{approvalRate}% approval rate</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Pending Review</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-yellow-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalQuestions - approvedQuestions}</div>
                            <p className="text-xs text-muted-foreground">Awaiting approval</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Needs Review</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{needsReview.length}</div>
                            <p className="text-xs text-muted-foreground">Low success rate (&lt;40%)</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Questions by Type */}
                <Card>
                    <CardHeader>
                        <CardTitle>Questions by Type</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {byType.length === 0 ? (
                                <p className="text-center text-muted-foreground">No questions yet</p>
                            ) : (
                                byType.map((type) => {
                                    const percentage =
                                        totalQuestions > 0 ? ((type.count / totalQuestions) * 100).toFixed(0) : 0;
                                    return (
                                        <div key={type.question_type} className="space-y-2">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="font-medium">
                                                    {typeLabels[type.question_type] || type.question_type}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {type.count} ({percentage}%)
                                                </span>
                                            </div>
                                            <div className="h-2 overflow-hidden rounded-full bg-secondary">
                                                <div
                                                    className="h-full bg-primary transition-all"
                                                    style={{ width: `${percentage}%` }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Top Performing Questions */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <TrendingUp className="h-5 w-5 text-green-600" />
                                Top Performing Questions
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Questions with highest success rates (&gt;10 attempts)
                            </p>
                        </CardHeader>
                        <CardContent>
                            {topPerforming.length === 0 ? (
                                <p className="text-center text-muted-foreground">
                                    No data yet. Questions need at least 10 attempts.
                                </p>
                            ) : (
                                <div className="space-y-4">
                                    {topPerforming.map((question, index) => {
                                        const difficulty =
                                            question.statistics?.computed_difficulty || 'medium';
                                        return (
                                            <div key={question.id} className="space-y-2 rounded-lg border p-4">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="flex items-center gap-2">
                                                        <Badge variant="outline" className="font-bold">
                                                            #{index + 1}
                                                        </Badge>
                                                        <Badge className={difficultyColors[difficulty]}>
                                                            {difficulty.charAt(0).toUpperCase() + difficulty.slice(1)}
                                                        </Badge>
                                                        {question.topic && (
                                                            <Badge variant="secondary">{question.topic.name}</Badge>
                                                        )}
                                                    </div>
                                                    <div className="text-right">
                                                        <div className="text-lg font-bold text-green-600">
                                                            {question.statistics.success_rate.toFixed(0)}%
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {question.statistics.times_answered} attempts
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    className="line-clamp-2 text-sm"
                                                    dangerouslySetInnerHTML={{ __html: question.question_text }}
                                                />
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Questions Needing Review */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <AlertTriangle className="h-5 w-5 text-red-600" />
                                Questions Needing Review
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Questions with success rate below 40% (&gt;10 attempts)
                            </p>
                        </CardHeader>
                        <CardContent>
                            {needsReview.length === 0 ? (
                                <div className="text-center">
                                    <CheckCircle className="mx-auto mb-2 h-8 w-8 text-green-600" />
                                    <p className="text-sm text-muted-foreground">
                                        Great! No questions need review.
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {needsReview.map((question) => {
                                        const difficulty =
                                            question.statistics?.computed_difficulty || 'hard';
                                        return (
                                            <div key={question.id} className="space-y-2 rounded-lg border p-4">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="flex items-center gap-2">
                                                        <Badge className={difficultyColors[difficulty]}>
                                                            {difficulty.charAt(0).toUpperCase() + difficulty.slice(1)}
                                                        </Badge>
                                                        {question.topic && (
                                                            <Badge variant="secondary">{question.topic.name}</Badge>
                                                        )}
                                                    </div>
                                                    <div className="text-right">
                                                        <div className="text-lg font-bold text-red-600">
                                                            {question.statistics.success_rate.toFixed(0)}%
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {question.statistics.times_answered} attempts
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    className="line-clamp-2 text-sm"
                                                    dangerouslySetInnerHTML={{ __html: question.question_text }}
                                                />
                                                <p className="text-xs text-red-600">
                                                    ⚠️ Consider revising question wording or marking as "Hard"
                                                </p>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

