import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
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
                    <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                        <CardHeader className="pb-3">
                            <CardTitle>Filters</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="exam"
                                        className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                                    >
                                        Select Exam
                                    </Label>
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
                                    <Label
                                        htmlFor="date_from"
                                        className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                                    >
                                        Date From
                                    </Label>
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
                                    <Label
                                        htmlFor="date_to"
                                        className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                                    >
                                        Date To
                                    </Label>
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

                            <div className="flex flex-wrap items-center gap-3 border-t border-border/70 pt-5">
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
                                <StatCard
                                    title="Total Attempts"
                                    value={analytics.total_attempts}
                                    description="Completed attempts included in this view"
                                    icon={Users}
                                    iconColor="text-primary"
                                />
                                <StatCard
                                    title="Pass Rate"
                                    value={`${analytics.pass_rate}%`}
                                    description={`Target score ${analytics.exam.passing_score}%`}
                                    icon={CheckCircle2}
                                    iconColor="text-primary"
                                />
                                <StatCard
                                    title="Average Score"
                                    value={`${analytics.average_score} / ${analytics.exam.total_points}`}
                                    description={`${analytics.average_percentage}% average performance`}
                                    icon={TrendingUp}
                                    iconColor="text-primary"
                                />
                                <StatCard
                                    title="Completion Rate"
                                    value={`${analytics.completion_rate}%`}
                                    description="Residents who fully completed the exam"
                                    icon={BarChart3}
                                    iconColor="text-primary"
                                />
                            </div>

                            <Card className="overflow-hidden border-primary/10 bg-[linear-gradient(135deg,rgba(248,244,255,0.98),rgba(255,255,255,0.94))]">
                                <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                                    <div className="space-y-1">
                                        <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                            Current exam focus
                                        </p>
                                        <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                            {analytics.exam.title}
                                        </h3>
                                        <p className="text-sm leading-6 text-muted-foreground">
                                            {analytics.exam.category || 'General exam'} with a passing score of{' '}
                                            {analytics.exam.passing_score}%.
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <Badge variant="secondary">
                                            {analytics.question_stats.length} questions analyzed
                                        </Badge>
                                        <Badge variant="outline">
                                            {analytics.year_level_stats.length} year-level segments
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>

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
                                                                    <Badge variant={stat.success_rate >= 70 ? 'default' : stat.success_rate >= 50 ? 'secondary' : 'destructive'}>
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
                                                                    <Badge variant={topic.success_rate >= 70 ? 'default' : topic.success_rate >= 50 ? 'secondary' : 'destructive'}>
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
                                                                    <Badge variant={stat.pass_rate >= 70 ? 'default' : stat.pass_rate >= 50 ? 'secondary' : 'destructive'}>
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
