import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Category Performance" />

            <AnalyticsLayout>
                <div className="space-y-6">
                    <div className="flex items-start justify-between gap-4">
                        <HeadingSmall
                            title="Category Performance"
                            description="Compare pass rates and response volume across exam categories."
                        />
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Filters</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                                    Categories
                                </CardTitle>
                                <Layers3 className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {categoryPerformance.summary.categories_count}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Distinct exam categories
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
                                    {categoryPerformance.summary.exams_covered}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Published exams in scope
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Attempts
                                </CardTitle>
                                <CheckCircle2 className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {categoryPerformance.summary.total_attempts}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Completed attempts included
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Average Pass Rate
                                </CardTitle>
                                <BarChart3 className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {categoryPerformance.summary.average_pass_rate}%
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Across displayed categories
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {categoryPerformance.categories.length > 0 ? (
                        <div className="space-y-6">
                            <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Best Performing Categories</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {categoryPerformance.top_categories.length > 0 ? (
                                            categoryPerformance.top_categories.map((category) => (
                                                <div
                                                    key={`top-${category.category}`}
                                                    className="flex items-center justify-between gap-3 rounded-md border px-4 py-3"
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

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Needs Attention</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {categoryPerformance.needs_attention_categories.length > 0 ? (
                                            categoryPerformance.needs_attention_categories.map(
                                                (category) => (
                                                    <div
                                                        key={`attention-${category.category}`}
                                                        className="flex items-center justify-between gap-3 rounded-md border px-4 py-3"
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

                            <Card>
                                <CardHeader>
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
                        <Card>
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
