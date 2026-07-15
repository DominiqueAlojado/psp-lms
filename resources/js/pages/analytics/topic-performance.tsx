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
import {
    BarChart3,
    BookOpenText,
    CheckCircle2,
    CircleAlert,
    FolderKanban,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Analytics',
        href: '/analytics/topic-performance',
    },
];

interface Exam {
    id: string;
    title: string;
    category: string | null;
    type: 'institution' | 'national';
}

interface TopicRow {
    topic: string;
    question_count: number;
    total_points: number;
    correct_answers: number;
    total_answers: number;
    exams_covered: number;
    success_rate: number;
}

interface TopicPerformanceData {
    summary: {
        topics_count: number;
        exams_covered: number;
        total_questions: number;
        total_responses: number;
        average_success_rate: number;
    };
    topics: TopicRow[];
    top_topics: TopicRow[];
    needs_attention_topics: TopicRow[];
}

interface PageProps {
    exams: Exam[];
    topicPerformance: TopicPerformanceData;
    filters: {
        exam?: string;
        date_from?: string;
        date_to?: string;
    };
    [key: string]: unknown;
}

function getSuccessBadgeVariant(rate: number) {
    if (rate >= 75) {
        return 'default';
    }

    if (rate >= 50) {
        return 'secondary';
    }

    return 'destructive';
}

export default function TopicPerformance() {
    const { exams, topicPerformance, filters } = usePage<PageProps>().props;

    const [examFilter, setExamFilter] = useState(
        filters.exam?.toString() || '',
    );
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleSearch = () => {
        router.get(
            '/analytics/topic-performance',
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
        router.get('/analytics/topic-performance', {}, { preserveState: true });
    };

    const hasActiveFilters =
        filters.exam || filters.date_from || filters.date_to;

    const selectedExamLabel = useMemo(() => {
        if (!examFilter) {
            return 'All exams';
        }

        return exams.find((exam) => exam.id === examFilter)?.title ?? 'Selected exam';
    }, [examFilter, exams]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Topic Performance" />

            <AnalyticsLayout>
                <div className="space-y-6">
                    <div className="flex items-start justify-between gap-4">
                        <HeadingSmall
                            title="Topic Performance"
                            description="See which topics are strongest, weakest, and most answered across your exam results."
                        />
                    </div>

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
                                                <SelectItem key={exam.id} value={exam.id}>
                                                    {exam.title}
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
                                        onChange={(e) => setDateFrom(e.target.value)}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="date_to">Date To</Label>
                                    <Input
                                        id="date_to"
                                        type="date"
                                        value={dateTo}
                                        onChange={(e) => setDateTo(e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="mt-4 flex items-center gap-2">
                                <Button onClick={handleSearch}>
                                    <BarChart3 className="mr-2 h-4 w-4" />
                                    Analyze
                                </Button>
                                {hasActiveFilters && (
                                    <Button variant="outline" onClick={clearFilters}>
                                        <X className="mr-2 h-4 w-4" />
                                        Clear Filters
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Topics Tracked
                                </CardTitle>
                                <BookOpenText className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {topicPerformance.summary.topics_count}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {selectedExamLabel}
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Exams Covered
                                </CardTitle>
                                <FolderKanban className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {topicPerformance.summary.exams_covered}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Exams included in this view
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Responses
                                </CardTitle>
                                <CheckCircle2 className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {topicPerformance.summary.total_responses}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Answered topic-level items
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Average Success Rate
                                </CardTitle>
                                <BarChart3 className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {topicPerformance.summary.average_success_rate}%
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Across all displayed topics
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {topicPerformance.topics.length > 0 ? (
                        <div className="space-y-6">
                            <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Strongest Topics</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {topicPerformance.top_topics.length > 0 ? (
                                            topicPerformance.top_topics.map((topic) => (
                                                <div
                                                    key={`top-${topic.topic}`}
                                                    className="flex items-center justify-between gap-3 rounded-md border px-4 py-3"
                                                >
                                                    <div className="min-w-0">
                                                        <p className="truncate font-medium">
                                                            {topic.topic}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {topic.correct_answers} correct out of{' '}
                                                            {topic.total_answers} responses
                                                        </p>
                                                    </div>
                                                    <Badge
                                                        variant={getSuccessBadgeVariant(
                                                            topic.success_rate,
                                                        )}
                                                    >
                                                        {topic.success_rate}%
                                                    </Badge>
                                                </div>
                                            ))
                                        ) : (
                                            <p className="text-sm text-muted-foreground">
                                                No answered topics yet for this filter.
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Needs Attention</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {topicPerformance.needs_attention_topics.length > 0 ? (
                                            topicPerformance.needs_attention_topics.map((topic) => (
                                                <div
                                                    key={`attention-${topic.topic}`}
                                                    className="flex items-center justify-between gap-3 rounded-md border px-4 py-3"
                                                >
                                                    <div className="min-w-0">
                                                        <p className="truncate font-medium">
                                                            {topic.topic}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {topic.question_count} questions across{' '}
                                                            {topic.exams_covered} exam
                                                            {topic.exams_covered === 1 ? '' : 's'}
                                                        </p>
                                                    </div>
                                                    <Badge
                                                        variant={getSuccessBadgeVariant(
                                                            topic.success_rate,
                                                        )}
                                                    >
                                                        {topic.success_rate}%
                                                    </Badge>
                                                </div>
                                            ))
                                        ) : (
                                            <p className="text-sm text-muted-foreground">
                                                No low-performing topics detected yet.
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Topic Results</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Topic</TableHead>
                                                    <TableHead>Exams</TableHead>
                                                    <TableHead>Questions</TableHead>
                                                    <TableHead>Total Points</TableHead>
                                                    <TableHead>Correct Answers</TableHead>
                                                    <TableHead>Total Answers</TableHead>
                                                    <TableHead>Success Rate</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {topicPerformance.topics.map((topic) => (
                                                    <TableRow key={topic.topic}>
                                                        <TableCell className="font-medium">
                                                            {topic.topic}
                                                        </TableCell>
                                                        <TableCell>
                                                            {topic.exams_covered}
                                                        </TableCell>
                                                        <TableCell>
                                                            {topic.question_count}
                                                        </TableCell>
                                                        <TableCell>
                                                            {topic.total_points}
                                                        </TableCell>
                                                        <TableCell>
                                                            {topic.correct_answers}
                                                        </TableCell>
                                                        <TableCell>
                                                            {topic.total_answers}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant={getSuccessBadgeVariant(
                                                                    topic.success_rate,
                                                                )}
                                                            >
                                                                {topic.success_rate}%
                                                            </Badge>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    ) : (
                        <Card>
                            <CardContent className="flex min-h-56 flex-col items-center justify-center gap-3 py-8 text-center">
                                <CircleAlert className="h-8 w-8 text-muted-foreground" />
                                <div className="space-y-1">
                                    <p className="font-medium">No topic performance data yet</p>
                                    <p className="text-sm text-muted-foreground">
                                        Publish exams and collect completed attempts to see topic-level
                                        strengths and weak areas here.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </AnalyticsLayout>
        </AppLayout>
    );
}
