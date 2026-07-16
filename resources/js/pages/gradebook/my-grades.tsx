import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { HelpTooltip } from '@/components/ui/help-tooltip';
import { Progress } from '@/components/ui/progress';
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
import { preserveOrgParam } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Award,
    CheckCircle2,
    TrendingDown,
    TrendingUp,
    XCircle,
} from 'lucide-react';

interface Stats {
    total_exams: number;
    total_institution_exams: number;
    total_national_exams: number;
    average_score: number;
    average_percentage: number;
    total_passed: number;
    total_failed: number;
    pass_rate: number;
    highest_score: number;
    lowest_score: number;
}

interface CategoryPerformance {
    category: string;
    exam_count: number;
    average_percentage: number;
    total_passed: number;
    total_failed: number;
    pass_rate: number;
}

interface TopicPerformance {
    topic: string;
    total_questions: number;
    correct_answers: number;
    accuracy: number;
    score_percentage: number;
}

interface RecentExam {
    type: string;
    title: string;
    category: string | null;
    score: number;
    total_points: number;
    percentage: number;
    passed: boolean;
    submitted_at: string;
}

interface PerformanceTrend {
    date: string;
    percentage: number;
}

interface Comparison {
    year_level: string;
    organization_name: string | null;
    comparison_group_label: string;
    comparison_mode: 'overall' | 'selected_exam';
    selected_exam_title: string | null;
    metric_label: string;
    resident_average_percentage: number;
    same_year_level_average_percentage: number;
    organization_average_percentage: number;
    same_year_level_gap: number;
    organization_gap: number;
    same_year_level_rank: number | null;
    same_year_level_total: number;
    organization_rank: number | null;
    organization_total: number;
    peer_names_visible: boolean;
    is_national_context: boolean;
    year_level_breakdown: Array<{
        year_level: string;
        average_percentage: number;
        total_residents: number;
        resident_gap: number;
        top_average_percentage: number;
    }>;
}

interface NationalStanding {
    exam_title: string;
    exam_year: number;
    national_ranking_enabled: boolean;
    institution_comparison_enabled: boolean;
    national_rank: number | null;
    institution_rank: number | null;
    percentile: number | null;
}

interface Props {
    stats: Stats;
    comparison: Comparison | null;
    comparisonExamOptions: Array<{
        value: string;
        label: string;
        submitted_at: string | null;
    }>;
    selectedComparisonExamId: string;
    nationalStanding: NationalStanding | null;
    categoryPerformance: CategoryPerformance[];
    topicPerformance: TopicPerformance[];
    recentExams: RecentExam[];
    performanceTrend: PerformanceTrend[];
}

export default function MyGrades({
    stats,
    comparison,
    comparisonExamOptions,
    selectedComparisonExamId,
    nationalStanding,
    categoryPerformance,
    topicPerformance,
    recentExams,
}: Props) {
    const page = usePage<{ auth: { currentOrganization?: { slug?: string | null } } }>();
    const currentOrgSlug = page.props.auth.currentOrganization?.slug;
    const getAccuracyBadgeClassName = (accuracy: number) => {
        if (accuracy >= 80) {
            return 'border-transparent bg-success-soft text-success';
        }

        if (accuracy >= 60) {
            return 'border-transparent bg-warning-soft text-warning';
        }

        return '';
    };

    const getMasteryProgressClassName = (accuracy: number) => {
        if (accuracy >= 80) {
            return '[&>[data-slot=progress-indicator]]:bg-emerald-500';
        }

        if (accuracy >= 60) {
            return '[&>[data-slot=progress-indicator]]:bg-amber-500';
        }

        return '[&>[data-slot=progress-indicator]]:bg-rose-500';
    };

    const applyComparisonExam = (value: string) => {
        router.get(
            preserveOrgParam('/my-grades', currentOrgSlug),
            {
                exam: value === 'overall' ? undefined : value,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <AppLayout>
            <Head title="My Grades" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold">My Grades</h1>
                    <p className="text-muted-foreground">
                        Track your performance and identify areas for
                        improvement
                    </p>
                </div>

                {/* Overall Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <div className="flex items-center gap-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Exams
                                </CardTitle>
                                <HelpTooltip
                                    content="The total number of graded exams included in your current grade summary, across institution and national exams."
                                    ariaLabel="Explain total exams"
                                />
                            </div>
                            <Award className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.total_exams}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {stats.total_institution_exams} institution,{' '}
                                {stats.total_national_exams} national
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <div className="flex items-center gap-2">
                                <CardTitle className="text-sm font-medium">
                                    Average Score
                                </CardTitle>
                                <HelpTooltip
                                    content="Your average percentage score across all completed exams in this view."
                                    ariaLabel="Explain average score"
                                />
                            </div>
                            <TrendingUp className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.average_percentage.toFixed(1)}%
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Across all exams
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <div className="flex items-center gap-2">
                                <CardTitle className="text-sm font-medium">
                                    Pass Rate
                                </CardTitle>
                                <HelpTooltip
                                    content="The percentage of your completed exams that met or exceeded the passing score."
                                    ariaLabel="Explain pass rate"
                                />
                            </div>
                            <CheckCircle2 className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.pass_rate.toFixed(1)}%
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {stats.total_passed} passed,{' '}
                                {stats.total_failed} failed
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <div className="flex items-center gap-2">
                                <CardTitle className="text-sm font-medium">
                                    Score Range
                                </CardTitle>
                                <HelpTooltip
                                    content="This shows your lowest and highest percentage scores among the exams included in this summary."
                                    ariaLabel="Explain score range"
                                />
                            </div>
                            <TrendingDown className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.lowest_score.toFixed(0)}% -{' '}
                                {stats.highest_score.toFixed(0)}%
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Lowest to highest
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {comparison && stats.total_exams > 0 && (
                    <Card>
                        <CardHeader className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <CardTitle>Peer Standing</CardTitle>
                                {comparison.comparison_mode === 'selected_exam'
                                    && comparison.selected_exam_title && (
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Showing comparison for {comparison.selected_exam_title}
                                    </p>
                                )}
                            </div>
                            <div className="w-full lg:max-w-sm">
                                <Select
                                    value={selectedComparisonExamId}
                                    onValueChange={applyComparisonExam}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select comparison scope" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="overall">
                                            Overall performance
                                        </SelectItem>
                                        {comparisonExamOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                                {option.submitted_at ? ` (${option.submitted_at})` : ''}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div
                                className={`grid gap-4 ${
                                    comparison.is_national_context
                                        ? 'md:grid-cols-2'
                                        : 'md:grid-cols-3'
                                }`}
                            >
                                <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                            {comparison.metric_label}
                                        </p>
                                        <HelpTooltip
                                            content={
                                                comparison.comparison_mode === 'selected_exam'
                                                    ? 'This is your score for the selected exam attempt.'
                                                    : 'This is your average performance across all completed exams in this view.'
                                            }
                                            ariaLabel="Explain selected score"
                                        />
                                    </div>
                                    <p className="mt-2 text-2xl font-semibold">
                                        {comparison.resident_average_percentage.toFixed(1)}%
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {comparison.comparison_mode === 'selected_exam'
                                            ? 'Your score for the selected exam'
                                            : 'Across completed exams'}
                                    </p>
                                </div>
                                {!comparison.is_national_context && (
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                                {comparison.comparison_group_label}
                                            </p>
                                            <HelpTooltip
                                                content="This shows the average score of residents in your same year level for the current comparison scope."
                                                ariaLabel="Explain year-level average"
                                            />
                                        </div>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {comparison.same_year_level_average_percentage.toFixed(1)}%
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {comparison.same_year_level_rank
                                                ? `Rank ${comparison.same_year_level_rank} of ${comparison.same_year_level_total}`
                                                : comparison.comparison_mode === 'selected_exam'
                                                  ? 'No same-year-level results for this exam'
                                                  : 'No same-year-level cohort data'}
                                        </p>
                                    </div>
                                )}
                                <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                            Organization Average
                                        </p>
                                        <HelpTooltip
                                            content="This is the average score across all residents in your organization for the selected exam or overall view."
                                            ariaLabel="Explain organization average"
                                        />
                                    </div>
                                    <p className="mt-2 text-2xl font-semibold">
                                        {comparison.organization_average_percentage.toFixed(1)}%
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {comparison.organization_rank
                                            ? `Rank ${comparison.organization_rank} of ${comparison.organization_total}`
                                            : comparison.comparison_mode === 'selected_exam'
                                              ? 'No organization results for this exam'
                                              : 'No organization cohort data'}
                                    </p>
                                </div>
                            </div>

                            <div
                                className={`grid gap-4 ${
                                    comparison.is_national_context
                                        ? 'md:grid-cols-1'
                                        : 'md:grid-cols-2'
                                }`}
                            >
                                {!comparison.is_national_context && (
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="text-sm font-medium">
                                                Versus {comparison.year_level}
                                            </p>
                                            <HelpTooltip
                                                content="This is the point difference between your score and your year-level cohort average. Positive means you are above the cohort average."
                                                ariaLabel="Explain year-level gap"
                                            />
                                        </div>
                                        <p className="mt-2 text-xl font-semibold">
                                            {comparison.same_year_level_gap >= 0 ? '+' : ''}
                                            {comparison.same_year_level_gap.toFixed(1)} pts
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Difference from your year-level average
                                        </p>
                                    </div>
                                )}
                                <div className="rounded-xl border border-border/70 bg-background/80 p-4 md:max-w-none">
                                    <div className="flex items-start justify-between gap-3">
                                        <p className="text-sm font-medium">
                                            Versus {comparison.organization_name || 'organization'}
                                        </p>
                                        <HelpTooltip
                                            content="This is the point difference between your score and your organization-wide average. Positive means you are above the organization average."
                                            ariaLabel="Explain organization gap"
                                        />
                                    </div>
                                    <p className="mt-2 text-xl font-semibold">
                                        {comparison.organization_gap >= 0 ? '+' : ''}
                                        {comparison.organization_gap.toFixed(1)} pts
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Difference from the organization-wide average
                                    </p>
                                </div>
                            </div>

                            {comparison.is_national_context
                                && comparison.year_level_breakdown.length > 0 && (
                                <div className="space-y-3 rounded-xl border border-border/70 bg-background/80 p-4">
                                    <div>
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="text-sm font-medium">
                                                In-Service Cohort by Year Level
                                            </p>
                                            <HelpTooltip
                                                content="This table compares your score context against each year-level cohort in the in-service exam view."
                                                ariaLabel="Explain in-service cohort table"
                                            />
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            Compare your average against each year-level group from first year to graduate.
                                        </p>
                                    </div>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>
                                                    <div className="flex items-center gap-2">
                                                        <span>Year Level</span>
                                                        <HelpTooltip
                                                            content="The resident training level represented by the cohort row."
                                                            ariaLabel="Explain year level"
                                                        />
                                                    </div>
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <span>Cohort Avg</span>
                                                        <HelpTooltip
                                                            content="The average score of residents in that year level."
                                                            ariaLabel="Explain cohort average"
                                                        />
                                                    </div>
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <span>Residents</span>
                                                        <HelpTooltip
                                                            content="The number of residents included in that year-level cohort."
                                                            ariaLabel="Explain resident count"
                                                        />
                                                    </div>
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <span>Gap</span>
                                                        <HelpTooltip
                                                            content="The point difference between your score and that cohort's average. Positive means you are above that cohort average."
                                                            ariaLabel="Explain cohort gap"
                                                        />
                                                    </div>
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <span>Top Avg</span>
                                                        <HelpTooltip
                                                            content="The highest average score recorded within that year-level cohort."
                                                            ariaLabel="Explain top average"
                                                        />
                                                    </div>
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {comparison.year_level_breakdown.map((row) => (
                                                <TableRow key={row.year_level}>
                                                    <TableCell className="font-medium">
                                                        {row.year_level}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {row.average_percentage.toFixed(1)}%
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {row.total_residents}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {row.resident_gap >= 0 ? '+' : ''}
                                                        {row.resident_gap.toFixed(1)} pts
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {row.top_average_percentage.toFixed(1)}%
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {nationalStanding && (
                    <Card>
                        <CardHeader>
                            <CardTitle>National Standing</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-sm font-medium">
                                    {nationalStanding.exam_title}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {nationalStanding.exam_year}
                                </p>
                            </div>

                            <div className="grid gap-4 md:grid-cols-3">
                                {nationalStanding.national_ranking_enabled && (
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                                National Rank
                                            </p>
                                            <HelpTooltip
                                                content="This is your rank among all residents included in the national exam results, when national ranking is enabled."
                                                ariaLabel="Explain national rank"
                                            />
                                        </div>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {nationalStanding.national_rank ?? 'N/A'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Visible for this exam
                                        </p>
                                    </div>
                                )}
                                {nationalStanding.institution_comparison_enabled && (
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                                Institution Rank
                                            </p>
                                            <HelpTooltip
                                                content="This is your rank within your institution for this exam, when institution comparison is enabled."
                                                ariaLabel="Explain institution rank"
                                            />
                                        </div>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {nationalStanding.institution_rank ?? 'N/A'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Visible for this exam
                                        </p>
                                    </div>
                                )}
                                {nationalStanding.national_ranking_enabled && (
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                                Percentile
                                            </p>
                                            <HelpTooltip
                                                content="Percentile shows the percentage of national examinees you scored higher than. Reference formula: ((total candidates - rank) / (total candidates - 1)) x 100. A higher percentile means stronger relative performance."
                                                ariaLabel="Explain percentile"
                                            />
                                        </div>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {nationalStanding.percentile !== null
                                                ? `${Number(nationalStanding.percentile).toFixed(1)}%`
                                                : 'N/A'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            National percentile
                                        </p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Performance by Category */}
                {categoryPerformance.length > 0 && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <CardTitle>Performance by Category</CardTitle>
                                <HelpTooltip
                                    content="This section summarizes how you perform across exam categories based on completed attempts."
                                    ariaLabel="Explain performance by category"
                                />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>
                                            <div className="flex items-center gap-2">
                                                <span>Category</span>
                                                <HelpTooltip
                                                    content="The exam category or grouping used for these attempts."
                                                    ariaLabel="Explain category column"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead className="text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <span>Exams</span>
                                                <HelpTooltip
                                                    content="The number of completed exams included in that category."
                                                    ariaLabel="Explain exams count"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead className="text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <span>Average</span>
                                                <HelpTooltip
                                                    content="Your average percentage score for exams in that category."
                                                    ariaLabel="Explain category average"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead className="text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <span>Pass Rate</span>
                                                <HelpTooltip
                                                    content="The percentage of exams in that category that you passed."
                                                    ariaLabel="Explain category pass rate"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead>
                                            <div className="flex items-center gap-2">
                                                <span>Performance</span>
                                                <HelpTooltip
                                                    content="A quick visual bar showing your average score level for that category."
                                                    ariaLabel="Explain category performance bar"
                                                />
                                            </div>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categoryPerformance.map((category) => (
                                        <TableRow key={category.category}>
                                            <TableCell className="font-medium">
                                                {category.category}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {category.exam_count}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {category.average_percentage.toFixed(
                                                    1,
                                                )}
                                                %
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {category.pass_rate.toFixed(1)}%
                                            </TableCell>
                                            <TableCell>
                                                <Progress
                                                    value={
                                                        category.average_percentage
                                                    }
                                                    className="h-2"
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* Performance by Topic */}
                {topicPerformance.length > 0 && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <CardTitle>Performance by Topic</CardTitle>
                                <HelpTooltip
                                    content="This section shows how accurately you answer questions within each topic."
                                    ariaLabel="Explain performance by topic"
                                />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>
                                            <div className="flex items-center gap-2">
                                                <span>Topic</span>
                                                <HelpTooltip
                                                    content="The topic or subject area the questions belong to."
                                                    ariaLabel="Explain topic column"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead className="text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <span>Questions</span>
                                                <HelpTooltip
                                                    content="The total number of answered questions recorded for that topic."
                                                    ariaLabel="Explain topic question count"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead className="text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <span>Correct</span>
                                                <HelpTooltip
                                                    content="The number of correct answers you achieved within that topic."
                                                    ariaLabel="Explain correct answers"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead className="text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <span>Accuracy</span>
                                                <HelpTooltip
                                                    content="Your correctness rate for that topic, shown as a percentage."
                                                    ariaLabel="Explain topic accuracy"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead>
                                            <div className="flex items-center gap-2">
                                                <span>Mastery</span>
                                                <HelpTooltip
                                                    content="A visual progress indicator of how strong your performance is in that topic."
                                                    ariaLabel="Explain topic mastery"
                                                />
                                            </div>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {topicPerformance.map((topic) => (
                                        <TableRow key={topic.topic}>
                                            <TableCell className="font-medium">
                                                {topic.topic}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {topic.total_questions}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {topic.correct_answers}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Badge
                                                    variant={
                                                        topic.accuracy >= 80
                                                            ? 'default'
                                                            : topic.accuracy >=
                                                                60
                                                              ? 'secondary'
                                                              : 'destructive'
                                                    }
                                                    className={getAccuracyBadgeClassName(
                                                        topic.accuracy,
                                                    )}
                                                >
                                                    {topic.accuracy.toFixed(1)}%
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Progress
                                                    value={topic.accuracy}
                                                    className={`h-2 ${getMasteryProgressClassName(
                                                        topic.accuracy,
                                                    )}`}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* Recent Exams */}
                {recentExams.length > 0 && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <CardTitle>Recent Exams</CardTitle>
                                <HelpTooltip
                                    content="This table lists your most recent completed exams and the outcome for each one."
                                    ariaLabel="Explain recent exams"
                                />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>
                                            <div className="flex items-center gap-2">
                                                <span>Exam</span>
                                                <HelpTooltip
                                                    content="The title of the completed exam."
                                                    ariaLabel="Explain exam title"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead>
                                            <div className="flex items-center gap-2">
                                                <span>Type</span>
                                                <HelpTooltip
                                                    content="Whether the exam belongs to an institution or national exam flow."
                                                    ariaLabel="Explain exam type"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead>
                                            <div className="flex items-center gap-2">
                                                <span>Category</span>
                                                <HelpTooltip
                                                    content="The exam category attached to that attempt, if available."
                                                    ariaLabel="Explain recent exam category"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead className="text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <span>Score</span>
                                                <HelpTooltip
                                                    content="Your achieved score, total possible points, and percentage for the exam."
                                                    ariaLabel="Explain recent exam score"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead className="text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <span>Result</span>
                                                <HelpTooltip
                                                    content="Whether the exam attempt passed or failed based on the required passing score."
                                                    ariaLabel="Explain recent exam result"
                                                />
                                            </div>
                                        </TableHead>
                                        <TableHead className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <span>Date</span>
                                                <HelpTooltip
                                                    content="The submission date recorded for the exam attempt."
                                                    ariaLabel="Explain recent exam date"
                                                />
                                            </div>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentExams.map((exam, index) => (
                                        <TableRow key={index}>
                                            <TableCell className="font-medium">
                                                {exam.title}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {exam.type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {exam.category || 'N/A'}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {exam.score} /{' '}
                                                {exam.total_points} (
                                                {exam.percentage.toFixed(1)}%)
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {exam.passed ? (
                                                    <Badge className="gap-1 border-transparent bg-success-soft text-success">
                                                        <CheckCircle2 className="size-3" />
                                                        Passed
                                                    </Badge>
                                                ) : (
                                                    <Badge
                                                        variant="destructive"
                                                        className="gap-1"
                                                    >
                                                        <XCircle className="size-3" />
                                                        Failed
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {new Date(
                                                    exam.submitted_at,
                                                ).toLocaleDateString()}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* Empty State */}
                {stats.total_exams === 0 && (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Award className="mb-4 size-12 text-muted-foreground" />
                            <h3 className="mb-2 text-lg font-semibold">
                                No exams completed yet
                            </h3>
                            <p className="text-center text-sm text-muted-foreground">
                                Your performance data will appear here after you
                                complete your first exam.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
