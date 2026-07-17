import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { RechartsShell } from '@/components/charts/recharts-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
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
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    BarChart3,
    BookOpenText,
    ChartColumnBig,
    CheckCircle2,
    CircleAlert,
    FolderKanban,
    Sparkles,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    Area,
    Bar,
    CartesianGrid,
    ComposedChart,
    Line,
    Scatter,
    XAxis,
    YAxis,
} from 'recharts';

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
    isResidentView?: boolean;
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

function buildTopicPerformanceInsight(topics: TopicRow[]) {
    if (topics.length === 0) {
        return [];
    }

    const highestSuccessRate = Math.max(
        ...topics.map((topic) => topic.success_rate),
    );
    const lowestSuccessRate = Math.min(
        ...topics.map((topic) => topic.success_rate),
    );
    const highestResponseVolume = Math.max(
        ...topics.map((topic) => topic.total_answers),
    );

    const strongestTopics = topics.filter(
        (topic) => topic.success_rate === highestSuccessRate,
    );
    const weakestTopics = topics.filter(
        (topic) => topic.success_rate === lowestSuccessRate,
    );
    const mostAnsweredTopics = topics.filter(
        (topic) => topic.total_answers === highestResponseVolume,
    );

    const insightItems = [
        {
            label: 'Strongest',
            topics: strongestTopics.map((topic) => topic.topic),
            value: `${highestSuccessRate.toFixed(1)}% success`,
        },
    ];

    const strongestTopicNames = new Set(
        strongestTopics.map((topic) => topic.topic),
    );
    const weakestTopicNames = new Set(weakestTopics.map((topic) => topic.topic));
    const hasDistinctWeakestTopic = [...weakestTopicNames].some(
        (topic) => !strongestTopicNames.has(topic),
    );

    if (hasDistinctWeakestTopic) {
        insightItems.push({
            label: 'Needs Focus',
            topics: weakestTopics.map((topic) => topic.topic),
            value: `${lowestSuccessRate.toFixed(1)}% success`,
        });
    }

    if (mostAnsweredTopics.length > 0) {
        insightItems.push({
            label: 'Most Answered',
            topics: mostAnsweredTopics.map((topic) => topic.topic),
            value: `${highestResponseVolume} recorded ${
                highestResponseVolume === 1 ? 'answer' : 'answers'
            }`,
        });
    }

    return insightItems;
}

export default function TopicPerformance() {
    const { exams, topicPerformance, filters, isResidentView = false } = usePage<PageProps>().props;
    const { auth } = usePage<SharedData>().props;
    const currentOrgSlug = auth.currentOrganization?.slug;

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
                org: currentOrgSlug || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setExamFilter('');
        setDateFrom('');
        setDateTo('');
        router.get('/analytics/topic-performance', { org: currentOrgSlug || undefined }, { preserveState: true });
    };

    const hasActiveFilters =
        filters.exam || filters.date_from || filters.date_to;

    const selectedExamLabel = useMemo(() => {
        if (!examFilter) {
            return 'All exams';
        }

        return exams.find((exam) => exam.id === examFilter)?.title ?? 'Selected exam';
    }, [examFilter, exams]);
    const topicOverviewChartConfig = {
        success_rate: {
            label: 'Success Rate',
            color: 'hsl(24 95% 53%)',
        },
        total_answers: {
            label: 'Total Answers',
            color: 'hsla(246 65% 58% / 0.35)',
        },
        question_count: {
            label: 'Questions',
            color: 'hsl(242 45% 47%)',
        },
        correct_answers: {
            label: 'Correct Answers',
            color: 'hsl(0 91% 57%)',
        },
    };
    const chartTopics = useMemo(() => {
        return [...topicPerformance.topics]
            .sort((first, second) => second.success_rate - first.success_rate)
            .slice(0, 8);
    }, [topicPerformance.topics]);

    const pageContent = (
        <div
            className={cn(
                'space-y-6',
                isResidentView && 'px-5 py-5 sm:px-6 lg:px-8 lg:py-6',
            )}
        >
            <HeadingSmall
                title={isResidentView ? 'My Topic Performance' : 'Topic Performance'}
                description={
                    isResidentView
                        ? 'See which topics are strongest, weakest, and most answered in your own completed exam results.'
                        : 'See which topics are strongest, weakest, and most answered across your exam results.'
                }
            />

            <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                <CardHeader className="pb-3">
                    <div className="space-y-1">
                        <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                            Filters
                        </p>
                        <CardTitle>{isResidentView ? 'Focus your topic report' : 'Focus the topic report'}</CardTitle>
                    </div>
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
                                        <SelectItem key={exam.id} value={exam.id}>
                                            {exam.title}
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
                                onChange={(e) => setDateFrom(e.target.value)}
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
                                onChange={(e) => setDateTo(e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-3 border-t border-border/70 pt-5">
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
                <StatCard
                    title="Topics Tracked"
                    value={topicPerformance.summary.topics_count}
                    description={selectedExamLabel}
                    icon={BookOpenText}
                    iconColor="text-primary"
                />
                <StatCard
                    title="Exams Covered"
                    value={topicPerformance.summary.exams_covered}
                    description="Exams included in this view"
                    icon={FolderKanban}
                    iconColor="text-primary"
                />
                <StatCard
                    title="Total Responses"
                    value={topicPerformance.summary.total_responses}
                    description="Answered topic-level items"
                    icon={CheckCircle2}
                    iconColor="text-primary"
                />
                <StatCard
                    title="Average Success Rate"
                    value={`${topicPerformance.summary.average_success_rate}%`}
                    description="Across all displayed topics"
                    icon={ChartColumnBig}
                    iconColor="text-primary"
                />
            </div>

            {topicPerformance.topics.length > 0 ? (
                <div className="space-y-6">
                    <div className="grid grid-cols-1 gap-4">
                        <RechartsShell
                            title="Topic Performance Overview"
                            description="Compare success rate, response volume, and correct-answer count across your top-performing topics in one view."
                            config={topicOverviewChartConfig}
                        >
                            <ComposedChart
                                data={chartTopics}
                                margin={{ left: 12, right: 12 }}
                            >
                                <CartesianGrid vertical={false} />
                                <XAxis
                                    dataKey="topic"
                                    tickLine={false}
                                    axisLine={false}
                                    tickMargin={8}
                                    interval={0}
                                    angle={-18}
                                    textAnchor="end"
                                    height={56}
                                />
                                <YAxis
                                    yAxisId="rate"
                                    tickLine={false}
                                    axisLine={false}
                                    tickMargin={8}
                                    domain={[0, 100]}
                                />
                                <YAxis
                                    yAxisId="count"
                                    orientation="right"
                                    tickLine={false}
                                    axisLine={false}
                                    tickMargin={8}
                                    allowDecimals={false}
                                />
                                <ChartTooltip
                                    content={<ChartTooltipContent />}
                                />
                                <Area
                                    yAxisId="count"
                                    type="monotone"
                                    dataKey="total_answers"
                                    stroke="var(--color-total_answers)"
                                    fill="var(--color-total_answers)"
                                    strokeWidth={1.5}
                                />
                                <Bar
                                    yAxisId="count"
                                    dataKey="question_count"
                                    fill="var(--color-question_count)"
                                    radius={[6, 6, 0, 0]}
                                    barSize={24}
                                />
                                <Line
                                    yAxisId="rate"
                                    type="monotone"
                                    dataKey="success_rate"
                                    stroke="var(--color-success_rate)"
                                    strokeWidth={2}
                                    dot={{
                                        fill: 'var(--color-success_rate)',
                                    }}
                                    activeDot={{ r: 5 }}
                                />
                                <Scatter
                                    yAxisId="count"
                                    dataKey="correct_answers"
                                    fill="var(--color-correct_answers)"
                                />
                            </ComposedChart>
                        </RechartsShell>
                        <div className="rounded-2xl border border-primary/15 bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_84%,white)_0%,color-mix(in_oklab,var(--color-card)_96%,var(--color-accent))_100%)] px-4 py-4 dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_62%,black)_0%,color-mix(in_oklab,var(--color-card)_92%,var(--color-accent))_100%)]">
                            <div className="flex items-start gap-3">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/12 text-primary">
                                    <Sparkles className="size-5" />
                                </div>
                                <div className="space-y-1">
                                    <p className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                        Quick insight
                                    </p>
                                    <div className="flex flex-wrap gap-2 pt-1">
                                        {buildTopicPerformanceInsight(chartTopics).map((insight) => (
                                            <div
                                                key={`${insight.label}-${insight.value}`}
                                                className="flex max-w-full flex-wrap items-center gap-2 rounded-2xl border border-primary/10 bg-background/80 px-3 py-2 shadow-sm"
                                            >
                                                <Badge
                                                    variant="secondary"
                                                    className="rounded-full border border-primary/10 bg-primary/10 px-2.5 py-1 text-[0.68rem] font-semibold tracking-[0.08em] text-primary uppercase"
                                                >
                                                    {insight.label}
                                                </Badge>
                                                <div className="flex flex-wrap gap-1.5">
                                                    {insight.topics.map((topic) => (
                                                        <Badge
                                                            key={`${insight.label}-${topic}`}
                                                            variant="secondary"
                                                            className="rounded-full border border-border/70 bg-background px-2.5 py-1 text-xs font-medium text-foreground"
                                                        >
                                                            {topic}
                                                        </Badge>
                                                    ))}
                                                </div>
                                                <span className="text-xs font-medium text-muted-foreground">
                                                    {insight.value}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <Card className="overflow-hidden border-primary/12 bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_88%,white)_0%,color-mix(in_oklab,var(--color-card)_96%,var(--color-accent))_100%)] dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_72%,black)_0%,color-mix(in_oklab,var(--color-card)_92%,var(--color-accent))_100%)]">
                        <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="space-y-1">
                                <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                    Topic focus
                                </p>
                                <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                    {selectedExamLabel}
                                </h3>
                                <p className="text-sm leading-6 text-muted-foreground">
                                    {topicPerformance.summary.total_questions} questions grouped into {topicPerformance.summary.topics_count} tracked topics.
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Badge variant="secondary">
                                    {topicPerformance.top_topics.length} strongest topics
                                </Badge>
                                <Badge variant="outline">
                                    {topicPerformance.needs_attention_topics.length} attention areas
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                        <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                            <CardHeader className="pb-3">
                                <CardTitle>Strongest Topics</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {topicPerformance.top_topics.length > 0 ? (
                                    topicPerformance.top_topics.map((topic) => (
                                        <div
                                            key={`top-${topic.topic}`}
                                            className="flex items-center justify-between gap-3 rounded-2xl border border-border/70 bg-background/80 px-4 py-3"
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

                        <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                            <CardHeader className="pb-3">
                                <CardTitle>Needs Attention</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {topicPerformance.needs_attention_topics.length > 0 ? (
                                    topicPerformance.needs_attention_topics.map((topic) => (
                                        <div
                                            key={`attention-${topic.topic}`}
                                            className="flex items-center justify-between gap-3 rounded-2xl border border-border/70 bg-background/80 px-4 py-3"
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

                    <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                        <CardHeader className="pb-3">
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
                <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                    <CardContent className="flex min-h-56 flex-col items-center justify-center gap-3 py-8 text-center">
                        <CircleAlert className="h-8 w-8 text-muted-foreground" />
                        <div className="space-y-1">
                            <p className="font-medium">No topic performance data yet</p>
                            <p className="text-sm text-muted-foreground">
                                {isResidentView
                                    ? 'Complete exams in this context to see your strongest and weakest topics here.'
                                    : 'Publish exams and collect completed attempts to see topic-level strengths and weak areas here.'}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            )}
        </div>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Topic Performance" />
            {isResidentView ? pageContent : <AnalyticsLayout>{pageContent}</AnalyticsLayout>}
        </AppLayout>
    );
}
