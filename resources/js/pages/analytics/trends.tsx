import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
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
    CalendarRange,
    CircleAlert,
    Minus,
    TrendingDown,
    TrendingUp,
    X,
} from 'lucide-react';
import { useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Line,
    LineChart,
    XAxis,
    YAxis,
} from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Analytics',
        href: '/analytics/trends',
    },
];

interface Exam {
    id: string;
    title: string;
    category: string | null;
    type: 'institution' | 'national';
}

interface TrendPeriod {
    period_key: string;
    period_label: string;
    attempts: number;
    passed_attempts: number;
    pass_rate: number;
    average_score: number;
    average_percentage: number;
}

interface TrendsData {
    summary: {
        periods_count: number;
        exams_covered: number;
        total_attempts: number;
        average_pass_rate: number;
        latest_period: string | null;
        pass_rate_change: number;
        average_percentage_change: number;
        direction: 'improving' | 'declining' | 'stable';
    };
    periods: TrendPeriod[];
    best_period: TrendPeriod | null;
    lowest_period: TrendPeriod | null;
}

interface PageProps {
    exams: Exam[];
    trends: TrendsData;
    filters: {
        exam?: string;
        date_from?: string;
        date_to?: string;
    };
    [key: string]: unknown;
}

function getRateBadgeVariant(rate: number) {
    if (rate >= 75) {
        return 'default';
    }

    if (rate >= 50) {
        return 'secondary';
    }

    return 'destructive';
}

function DirectionIcon({ direction }: { direction: TrendsData['summary']['direction'] }) {
    if (direction === 'improving') {
        return <TrendingUp className="h-4 w-4 text-green-600" />;
    }

    if (direction === 'declining') {
        return <TrendingDown className="h-4 w-4 text-destructive" />;
    }

    return <Minus className="h-4 w-4 text-muted-foreground" />;
}

export default function Trends() {
    const { exams, trends, filters } = usePage<PageProps>().props;

    const [examFilter, setExamFilter] = useState(
        filters.exam?.toString() || '',
    );
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleSearch = () => {
        router.get(
            '/analytics/trends',
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
        router.get('/analytics/trends', {}, { preserveState: true });
    };

    const hasActiveFilters =
        filters.exam || filters.date_from || filters.date_to;

    const performanceChartConfig = {
        pass_rate: {
            label: 'Pass Rate',
            color: 'hsl(24 95% 53%)',
        },
        average_percentage: {
            label: 'Average Score %',
            color: 'hsl(217 91% 60%)',
        },
    };

    const attemptsChartConfig = {
        attempts: {
            label: 'Attempts',
            color: 'hsl(142 71% 45%)',
        },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Trends" />

            <AnalyticsLayout>
                <div className="space-y-6">
                    <div className="flex items-start justify-between gap-4">
                        <HeadingSmall
                            title="Trends"
                            description="Track attempts, pass rate, and average score movement over time."
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
                                    Time Periods
                                </CardTitle>
                                <CalendarRange className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {trends.summary.periods_count}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Monthly buckets with activity
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Exams Covered
                                </CardTitle>
                                <BarChart3 className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {trends.summary.exams_covered}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Exams included in this view
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Attempts
                                </CardTitle>
                                <TrendingUp className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {trends.summary.total_attempts}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Completed attempts in trend view
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Overall Direction
                                </CardTitle>
                                <DirectionIcon direction={trends.summary.direction} />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold capitalize">
                                    {trends.summary.direction}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Pass rate change {trends.summary.pass_rate_change} pts
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {trends.periods.length > 0 ? (
                        <div className="space-y-6">
                            <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Performance Trend</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ChartContainer
                                            config={performanceChartConfig}
                                            className="h-72 w-full"
                                        >
                                            <LineChart data={trends.periods}>
                                                <CartesianGrid vertical={false} />
                                                <XAxis
                                                    dataKey="period_label"
                                                    tickLine={false}
                                                    axisLine={false}
                                                    tickMargin={8}
                                                />
                                                <YAxis
                                                    tickLine={false}
                                                    axisLine={false}
                                                    tickMargin={8}
                                                    domain={[0, 100]}
                                                />
                                                <ChartTooltip
                                                    content={
                                                        <ChartTooltipContent />
                                                    }
                                                />
                                                <Line
                                                    type="monotone"
                                                    dataKey="pass_rate"
                                                    stroke="var(--color-pass_rate)"
                                                    strokeWidth={2}
                                                    dot={{
                                                        fill: 'var(--color-pass_rate)',
                                                    }}
                                                    activeDot={{ r: 5 }}
                                                />
                                                <Line
                                                    type="monotone"
                                                    dataKey="average_percentage"
                                                    stroke="var(--color-average_percentage)"
                                                    strokeWidth={2}
                                                    dot={{
                                                        fill: 'var(--color-average_percentage)',
                                                    }}
                                                    activeDot={{ r: 5 }}
                                                />
                                            </LineChart>
                                        </ChartContainer>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Attempt Volume</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ChartContainer
                                            config={attemptsChartConfig}
                                            className="h-72 w-full"
                                        >
                                            <BarChart data={trends.periods}>
                                                <CartesianGrid vertical={false} />
                                                <XAxis
                                                    dataKey="period_label"
                                                    tickLine={false}
                                                    axisLine={false}
                                                    tickMargin={8}
                                                />
                                                <YAxis
                                                    tickLine={false}
                                                    axisLine={false}
                                                    tickMargin={8}
                                                />
                                                <ChartTooltip
                                                    content={
                                                        <ChartTooltipContent />
                                                    }
                                                />
                                                <Bar
                                                    dataKey="attempts"
                                                    fill="var(--color-attempts)"
                                                    radius={[6, 6, 0, 0]}
                                                />
                                            </BarChart>
                                        </ChartContainer>
                                    </CardContent>
                                </Card>
                            </div>

                            <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Best Period</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {trends.best_period ? (
                                            <div className="space-y-2 rounded-md border px-4 py-3">
                                                <div className="flex items-center justify-between gap-3">
                                                    <p className="font-medium">
                                                        {trends.best_period.period_label}
                                                    </p>
                                                    <Badge
                                                        variant={getRateBadgeVariant(
                                                            trends.best_period.pass_rate,
                                                        )}
                                                    >
                                                        {trends.best_period.pass_rate}%
                                                    </Badge>
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    {trends.best_period.passed_attempts} passed out of{' '}
                                                    {trends.best_period.attempts} attempts
                                                </p>
                                            </div>
                                        ) : null}
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Lowest Period</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {trends.lowest_period ? (
                                            <div className="space-y-2 rounded-md border px-4 py-3">
                                                <div className="flex items-center justify-between gap-3">
                                                    <p className="font-medium">
                                                        {trends.lowest_period.period_label}
                                                    </p>
                                                    <Badge
                                                        variant={getRateBadgeVariant(
                                                            trends.lowest_period.pass_rate,
                                                        )}
                                                    >
                                                        {trends.lowest_period.pass_rate}%
                                                    </Badge>
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    Average score {trends.lowest_period.average_score} and{' '}
                                                    {trends.lowest_period.average_percentage}% average
                                                </p>
                                            </div>
                                        ) : null}
                                    </CardContent>
                                </Card>
                            </div>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Trend by Period</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Period</TableHead>
                                                    <TableHead>Attempts</TableHead>
                                                    <TableHead>Passed</TableHead>
                                                    <TableHead>Average Score</TableHead>
                                                    <TableHead>Average %</TableHead>
                                                    <TableHead>Pass Rate</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {trends.periods.map((period) => (
                                                    <TableRow key={period.period_key}>
                                                        <TableCell className="font-medium">
                                                            {period.period_label}
                                                        </TableCell>
                                                        <TableCell>{period.attempts}</TableCell>
                                                        <TableCell>{period.passed_attempts}</TableCell>
                                                        <TableCell>{period.average_score}</TableCell>
                                                        <TableCell>{period.average_percentage}%</TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant={getRateBadgeVariant(
                                                                    period.pass_rate,
                                                                )}
                                                            >
                                                                {period.pass_rate}%
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
                                    <p className="font-medium">No trend data yet</p>
                                    <p className="text-sm text-muted-foreground">
                                        Completed attempts with submission dates are needed before
                                        trends can be displayed here.
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
