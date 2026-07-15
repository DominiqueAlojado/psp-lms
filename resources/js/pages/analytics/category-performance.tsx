import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
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
    CheckCircle2,
    CircleAlert,
    FolderKanban,
    Layers3,
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
        href: '/analytics/category-performance',
    },
];

interface CategoryRow {
    category: string;
    exams_count: number;
    total_attempts: number;
    passed_attempts: number;
    total_questions: number;
    pass_rate: number;
    average_score: number;
    average_percentage: number;
}

interface CategoryPerformanceData {
    summary: {
        categories_count: number;
        exams_covered: number;
        total_attempts: number;
        average_pass_rate: number;
    };
    categories: CategoryRow[];
    top_categories: CategoryRow[];
    needs_attention_categories: CategoryRow[];
}

interface PageProps {
    categoryPerformance: CategoryPerformanceData;
    filters: {
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

export default function CategoryPerformance() {
    const { categoryPerformance, filters } = usePage<PageProps>().props;

    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleSearch = () => {
        router.get(
            '/analytics/category-performance',
            {
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setDateFrom('');
        setDateTo('');
        router.get('/analytics/category-performance', {}, { preserveState: true });
    };

    const hasActiveFilters = filters.date_from || filters.date_to;
    const passRateChartConfig = {
        pass_rate: {
            label: 'Pass Rate',
            color: 'hsl(24 95% 53%)',
        },
        average_percentage: {
            label: 'Average %',
            color: 'hsl(217 91% 60%)',
        },
    };
    const attemptsChartConfig = {
        total_attempts: {
            label: 'Attempts',
            color: 'hsl(142 71% 45%)',
        },
        passed_attempts: {
            label: 'Passed',
            color: 'hsl(221 83% 53%)',
        },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Category Performance" />

            <AnalyticsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Category Performance"
                        description="Compare pass rates and response volume across exam categories."
                    />

                    <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                        <CardHeader className="pb-3">
                            <div className="space-y-1">
                                <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                    Filters
                                </p>
                                <CardTitle>Set the category range</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                            title="Categories"
                            value={categoryPerformance.summary.categories_count}
                            description="Distinct exam categories"
                            icon={Layers3}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Exams Covered"
                            value={categoryPerformance.summary.exams_covered}
                            description="Published exams in scope"
                            icon={FolderKanban}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Total Attempts"
                            value={categoryPerformance.summary.total_attempts}
                            description="Completed attempts included"
                            icon={CheckCircle2}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Average Pass Rate"
                            value={`${categoryPerformance.summary.average_pass_rate}%`}
                            description="Across displayed categories"
                            icon={BarChart3}
                            iconColor="text-primary"
                        />
                    </div>

                    {categoryPerformance.categories.length > 0 ? (
                        <div className="space-y-6">
                            <Card className="overflow-hidden border-primary/10 bg-[linear-gradient(135deg,rgba(248,244,255,0.98),rgba(255,255,255,0.94))]">
                                <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                                    <div className="space-y-1">
                                        <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                            Category focus
                                        </p>
                                        <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                            Cross-category performance overview
                                        </h3>
                                        <p className="text-sm leading-6 text-muted-foreground">
                                            Compare pass rate, attempt volume, and score distribution across {categoryPerformance.summary.categories_count} exam categories.
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <Badge variant="secondary">
                                            {categoryPerformance.top_categories.length} top categories
                                        </Badge>
                                        <Badge variant="outline">
                                            {categoryPerformance.needs_attention_categories.length} need attention
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                                    <CardHeader className="pb-3">
                                        <CardTitle>Pass Rate by Category</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ChartContainer
                                            config={passRateChartConfig}
                                            className="h-80 w-full"
                                        >
                                            <LineChart
                                                data={categoryPerformance.categories}
                                                margin={{ left: 12, right: 12 }}
                                            >
                                                <CartesianGrid vertical={false} />
                                                <XAxis
                                                    dataKey="category"
                                                    tickLine={false}
                                                    axisLine={false}
                                                    tickMargin={8}
                                                    interval={0}
                                                    angle={-18}
                                                    textAnchor="end"
                                                    height={56}
                                                />
                                                <YAxis
                                                    tickLine={false}
                                                    axisLine={false}
                                                    tickMargin={8}
                                                    domain={[0, 100]}
                                                />
                                                <ChartTooltip
                                                    content={<ChartTooltipContent />}
                                                />
                                                <Line
                                                    type="monotone"
                                                    dataKey="pass_rate"
                                                    stroke="var(--color-pass_rate)"
                                                    strokeWidth={2}
                                                    dot={{ fill: 'var(--color-pass_rate)' }}
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

                                <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                                    <CardHeader className="pb-3">
                                        <CardTitle>Attempt Volume by Category</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ChartContainer
                                            config={attemptsChartConfig}
                                            className="h-80 w-full"
                                        >
                                            <BarChart
                                                data={categoryPerformance.categories}
                                                margin={{ left: 12, right: 12 }}
                                            >
                                                <CartesianGrid vertical={false} />
                                                <XAxis
                                                    dataKey="category"
                                                    tickLine={false}
                                                    axisLine={false}
                                                    tickMargin={8}
                                                    interval={0}
                                                    angle={-18}
                                                    textAnchor="end"
                                                    height={56}
                                                />
                                                <YAxis
                                                    tickLine={false}
                                                    axisLine={false}
                                                    tickMargin={8}
                                                />
                                                <ChartTooltip
                                                    content={<ChartTooltipContent />}
                                                />
                                                <Bar
                                                    dataKey="total_attempts"
                                                    fill="var(--color-total_attempts)"
                                                    radius={[6, 6, 0, 0]}
                                                />
                                                <Bar
                                                    dataKey="passed_attempts"
                                                    fill="var(--color-passed_attempts)"
                                                    radius={[6, 6, 0, 0]}
                                                />
                                            </BarChart>
                                        </ChartContainer>
                                    </CardContent>
                                </Card>
                            </div>

                            <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                                    <CardHeader className="pb-3">
                                        <CardTitle>Best Performing Categories</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {categoryPerformance.top_categories.length > 0 ? (
                                            categoryPerformance.top_categories.map((category) => (
                                                <div
                                                    key={`top-${category.category}`}
                                                    className="flex items-center justify-between gap-3 rounded-2xl border border-border/70 bg-background/80 px-4 py-3"
                                                >
                                                    <div className="min-w-0">
                                                        <p className="truncate font-medium">
                                                            {category.category}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {category.passed_attempts} passed out of{' '}
                                                            {category.total_attempts} attempts
                                                        </p>
                                                    </div>
                                                    <Badge
                                                        variant={getRateBadgeVariant(
                                                            category.pass_rate,
                                                        )}
                                                    >
                                                        {category.pass_rate}%
                                                    </Badge>
                                                </div>
                                            ))
                                        ) : (
                                            <p className="text-sm text-muted-foreground">
                                                No completed attempts yet for these categories.
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>

                                <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                                    <CardHeader className="pb-3">
                                        <CardTitle>Needs Attention</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {categoryPerformance.needs_attention_categories.length > 0 ? (
                                            categoryPerformance.needs_attention_categories.map(
                                                (category) => (
                                                    <div
                                                        key={`attention-${category.category}`}
                                                        className="flex items-center justify-between gap-3 rounded-2xl border border-border/70 bg-background/80 px-4 py-3"
                                                    >
                                                        <div className="min-w-0">
                                                            <p className="truncate font-medium">
                                                                {category.category}
                                                            </p>
                                                            <p className="text-sm text-muted-foreground">
                                                                {category.exams_count} exam
                                                                {category.exams_count === 1
                                                                    ? ''
                                                                    : 's'}{' '}
                                                                in this category
                                                            </p>
                                                        </div>
                                                        <Badge
                                                            variant={getRateBadgeVariant(
                                                                category.pass_rate,
                                                            )}
                                                        >
                                                            {category.pass_rate}%
                                                        </Badge>
                                                    </div>
                                                ),
                                            )
                                        ) : (
                                            <p className="text-sm text-muted-foreground">
                                                No low-performing categories detected yet.
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>

                            <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                                <CardHeader className="pb-3">
                                    <CardTitle>Category Results</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Category</TableHead>
                                                    <TableHead>Exams</TableHead>
                                                    <TableHead>Total Questions</TableHead>
                                                    <TableHead>Total Attempts</TableHead>
                                                    <TableHead>Passed</TableHead>
                                                    <TableHead>Average Score</TableHead>
                                                    <TableHead>Average %</TableHead>
                                                    <TableHead>Pass Rate</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {categoryPerformance.categories.map((category) => (
                                                    <TableRow key={category.category}>
                                                        <TableCell className="font-medium">
                                                            {category.category}
                                                        </TableCell>
                                                        <TableCell>
                                                            {category.exams_count}
                                                        </TableCell>
                                                        <TableCell>
                                                            {category.total_questions}
                                                        </TableCell>
                                                        <TableCell>
                                                            {category.total_attempts}
                                                        </TableCell>
                                                        <TableCell>
                                                            {category.passed_attempts}
                                                        </TableCell>
                                                        <TableCell>
                                                            {category.average_score}
                                                        </TableCell>
                                                        <TableCell>
                                                            {category.average_percentage}%
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant={getRateBadgeVariant(
                                                                    category.pass_rate,
                                                                )}
                                                            >
                                                                {category.pass_rate}%
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
                        <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                            <CardContent className="flex min-h-56 flex-col items-center justify-center gap-3 py-8 text-center">
                                <CircleAlert className="h-8 w-8 text-muted-foreground" />
                                <div className="space-y-1">
                                    <p className="font-medium">No category performance data yet</p>
                                    <p className="text-sm text-muted-foreground">
                                        Publish exams and collect completed attempts to compare
                                        category-level performance here.
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
