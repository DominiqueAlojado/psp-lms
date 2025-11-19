import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AnalyticsLayout from '@/layouts/analytics/analytics-layout';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { BarChart3, CheckCircle2, TrendingUp, Users, X } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Analytics',
        href: '/analytics/exam-analytics',
    },
];

interface Exam {
    id: string;
    title: string;
    category: string | null;
    type: 'institution' | 'national';
}

interface QuestionStat {
    question_id: number;
    question_text: string;
    topic: string;
    points: number;
    times_answered: number;
    times_correct: number;
    success_rate: number;
}

interface TopicBreakdown {
    topic: string;
    question_count: number;
    total_points: number;
    correct_answers: number;
    total_answers: number;
    success_rate: number;
}

interface YearLevelStat {
    year_level: string;
    count: number;
    average_score: number;
    passed: number;
    pass_rate: number;
}

interface ExamAnalytics {
    exam: {
        id: number;
        title: string;
        category: string | null;
        total_points: number;
        passing_score: number;
    };
    total_attempts: number;
    pass_rate: number;
    average_score: number;
    average_percentage: number;
    completion_rate: number;
    question_stats: QuestionStat[];
    topic_breakdown: TopicBreakdown[];
    year_level_stats: YearLevelStat[];
}

interface PageProps {
    exams: Exam[];
    analytics: ExamAnalytics | null;
    filters: {
        exam?: string;
        date_from?: string;
        date_to?: string;
    };
    [key: string]: unknown;
}

export default function ExamAnalytics() {
    const { exams, analytics, filters } = usePage<PageProps>().props;

    const [examFilter, setExamFilter] = useState(
        filters.exam?.toString() || '',
    );
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleSearch = () => {
        router.get(
            '/analytics/exam-analytics',
            {
                exam: examFilter || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setExamFilter('');
        setDateFrom('');
        setDateTo('');
        router.get('/analytics/exam-analytics', {}, { preserveState: true });
    };

    const hasActiveFilters =
        filters.exam || filters.date_from || filters.date_to;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Exam Analytics" />

            <AnalyticsLayout>
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex items-start justify-between gap-4">
                        <HeadingSmall
                            title="Exam Analytics"
                            description="Detailed statistics and insights for specific exams"
                        />
                    </div>

                    {/* Filters */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Filters</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="exam">Select Exam</Label>
                                    <Select
                                        value={examFilter}
                                        onValueChange={setExamFilter}
                                    >
                                        <SelectTrigger id="exam">
                                            <SelectValue placeholder="All Exams" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                All Exams
                                            </SelectItem>
                                            {exams.map((exam) => (
                                                <SelectItem
                                                    key={exam.id}
                                                    value={exam.id}
                                                >
                                                    {exam.title}
                                                    {exam.type ===
                                                        'national' && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="ml-2"
                                                        >
                                                            National
                                                        </Badge>
                                                    )}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="date_from">Date From</Label>
                                    <Input
                                        id="date_from"
                                        type="date"
                                        value={dateFrom}
                                        onChange={(e) =>
                                            setDateFrom(e.target.value)
                                        }
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="date_to">Date To</Label>
                                    <Input
                                        id="date_to"
                                        type="date"
                                        value={dateTo}
                                        onChange={(e) =>
                                            setDateTo(e.target.value)
                                        }
                                    />
                                </div>
                            </div>

                            <div className="mt-4 flex items-center gap-2">
                                <Button onClick={handleSearch}>
                                    <BarChart3 className="mr-2 h-4 w-4" />
                                    Analyze
                                </Button>
                                {hasActiveFilters && (
                                    <Button
                                        variant="outline"
                                        onClick={clearFilters}
                                    >
                                        <X className="mr-2 h-4 w-4" />
                                        Clear Filters
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Analytics Results */}
                    {analytics && (
                        <div className="space-y-6">
                            {/* Key Metrics */}
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">
                                            Total Attempts
                                        </CardTitle>
                                        <Users className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">
                                            {analytics.total_attempts}
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">
                                            Pass Rate
                                        </CardTitle>
                                        <CheckCircle2 className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">
                                            {analytics.pass_rate}%
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">
                                            Average Score
                                        </CardTitle>
                                        <TrendingUp className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">
                                            {analytics.average_score} /{' '}
                                            {analytics.exam.total_points}
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {analytics.average_percentage}%
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">
                                            Completion Rate
                                        </CardTitle>
                                        <BarChart3 className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">
                                            {analytics.completion_rate}%
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Question Statistics */}
                            {analytics.question_stats.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>
                                            Question Performance
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>
                                                            Question
                                                        </TableHead>
                                                        <TableHead>
                                                            Topic
                                                        </TableHead>
                                                        <TableHead>
                                                            Points
                                                        </TableHead>
                                                        <TableHead>
                                                            Times Answered
                                                        </TableHead>
                                                        <TableHead>
                                                            Times Correct
                                                        </TableHead>
                                                        <TableHead>
                                                            Success Rate
                                                        </TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {analytics.question_stats.map(
                                                        (stat) => (
                                                            <TableRow
                                                                key={
                                                                    stat.question_id
                                                                }
                                                            >
                                                                <TableCell className="max-w-md truncate">
                                                                    {
                                                                        stat.question_text
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    {stat.topic}
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        stat.points
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        stat.times_answered
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        stat.times_correct
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    <Badge
                                                                        variant={
                                                                            stat.success_rate >=
                                                                            70
                                                                                ? 'default'
                                                                                : stat.success_rate >=
                                                                                    50
                                                                                  ? 'secondary'
                                                                                  : 'destructive'
                                                                        }
                                                                    >
                                                                        {
                                                                            stat.success_rate
                                                                        }
                                                                        %
                                                                    </Badge>
                                                                </TableCell>
                                                            </TableRow>
                                                        ),
                                                    )}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Topic Breakdown */}
                            {analytics.topic_breakdown.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Topic Breakdown</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>
                                                            Topic
                                                        </TableHead>
                                                        <TableHead>
                                                            Questions
                                                        </TableHead>
                                                        <TableHead>
                                                            Total Points
                                                        </TableHead>
                                                        <TableHead>
                                                            Correct Answers
                                                        </TableHead>
                                                        <TableHead>
                                                            Total Answers
                                                        </TableHead>
                                                        <TableHead>
                                                            Success Rate
                                                        </TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {analytics.topic_breakdown.map(
                                                        (topic, index) => (
                                                            <TableRow
                                                                key={index}
                                                            >
                                                                <TableCell>
                                                                    {
                                                                        topic.topic
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        topic.question_count
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        topic.total_points
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        topic.correct_answers
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        topic.total_answers
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    <Badge
                                                                        variant={
                                                                            topic.success_rate >=
                                                                            70
                                                                                ? 'default'
                                                                                : topic.success_rate >=
                                                                                    50
                                                                                  ? 'secondary'
                                                                                  : 'destructive'
                                                                        }
                                                                    >
                                                                        {
                                                                            topic.success_rate
                                                                        }
                                                                        %
                                                                    </Badge>
                                                                </TableCell>
                                                            </TableRow>
                                                        ),
                                                    )}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Year Level Statistics */}
                            {analytics.year_level_stats.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>
                                            Performance by Year Level
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>
                                                            Year Level
                                                        </TableHead>
                                                        <TableHead>
                                                            Attempts
                                                        </TableHead>
                                                        <TableHead>
                                                            Average Score
                                                        </TableHead>
                                                        <TableHead>
                                                            Passed
                                                        </TableHead>
                                                        <TableHead>
                                                            Pass Rate
                                                        </TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {analytics.year_level_stats.map(
                                                        (stat, index) => (
                                                            <TableRow
                                                                key={index}
                                                            >
                                                                <TableCell>
                                                                    {
                                                                        stat.year_level
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    {stat.count}
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        stat.average_score
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        stat.passed
                                                                    }
                                                                </TableCell>
                                                                <TableCell>
                                                                    <Badge
                                                                        variant={
                                                                            stat.pass_rate >=
                                                                            70
                                                                                ? 'default'
                                                                                : stat.pass_rate >=
                                                                                    50
                                                                                  ? 'secondary'
                                                                                  : 'destructive'
                                                                        }
                                                                    >
                                                                        {
                                                                            stat.pass_rate
                                                                        }
                                                                        %
                                                                    </Badge>
                                                                </TableCell>
                                                            </TableRow>
                                                        ),
                                                    )}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    )}

                    {/* No Results Message */}
                    {!analytics && examFilter && (
                        <Card>
                            <CardContent className="py-8 text-center">
                                <p className="text-muted-foreground">
                                    Select an exam and click "Analyze" to view
                                    detailed analytics.
                                </p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </AnalyticsLayout>
        </AppLayout>
    );
}
