import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
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
    nationalStanding: NationalStanding | null;
    categoryPerformance: CategoryPerformance[];
    topicPerformance: TopicPerformance[];
    recentExams: RecentExam[];
    performanceTrend: PerformanceTrend[];
}

export default function MyGrades({
    stats,
    comparison,
    nationalStanding,
    categoryPerformance,
    topicPerformance,
    recentExams,
}: Props) {
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
                            <CardTitle className="text-sm font-medium">
                                Total Exams
                            </CardTitle>
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
                            <CardTitle className="text-sm font-medium">
                                Average Score
                            </CardTitle>
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
                            <CardTitle className="text-sm font-medium">
                                Pass Rate
                            </CardTitle>
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
                            <CardTitle className="text-sm font-medium">
                                Score Range
                            </CardTitle>
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
                        <CardHeader>
                            <CardTitle>Peer Standing</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                    <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                        Your Average
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold">
                                        {comparison.resident_average_percentage.toFixed(1)}%
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Across completed exams
                                    </p>
                                </div>
                                {!comparison.is_national_context && (
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                            {comparison.comparison_group_label}
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {comparison.same_year_level_average_percentage.toFixed(1)}%
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {comparison.same_year_level_rank
                                                ? `Rank ${comparison.same_year_level_rank} of ${comparison.same_year_level_total}`
                                                : 'No same-year-level cohort data'}
                                        </p>
                                    </div>
                                )}
                                <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                    <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                        Organization Average
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold">
                                        {comparison.organization_average_percentage.toFixed(1)}%
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {comparison.organization_rank
                                            ? `Rank ${comparison.organization_rank} of ${comparison.organization_total}`
                                            : 'No organization cohort data'}
                                    </p>
                                </div>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                {!comparison.is_national_context && (
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <p className="text-sm font-medium">
                                            Versus {comparison.year_level}
                                        </p>
                                        <p className="mt-2 text-xl font-semibold">
                                            {comparison.same_year_level_gap >= 0 ? '+' : ''}
                                            {comparison.same_year_level_gap.toFixed(1)} pts
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Difference from your year-level average
                                        </p>
                                    </div>
                                )}
                                <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                    <p className="text-sm font-medium">
                                        Versus {comparison.organization_name || 'organization'}
                                    </p>
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
                                        <p className="text-sm font-medium">
                                            In-Service Cohort by Year Level
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Compare your average against each year-level group from first year to graduate.
                                        </p>
                                    </div>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Year Level</TableHead>
                                                <TableHead className="text-center">
                                                    Cohort Avg
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    Residents
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    Gap
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    Top Avg
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
                                        <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                            National Rank
                                        </p>
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
                                        <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                            Institution Rank
                                        </p>
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
                                        <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                            Percentile
                                        </p>
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
                            <CardTitle>Performance by Category</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Category</TableHead>
                                        <TableHead className="text-center">
                                            Exams
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Average
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Pass Rate
                                        </TableHead>
                                        <TableHead>Performance</TableHead>
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
                            <CardTitle>Performance by Topic</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Topic</TableHead>
                                        <TableHead className="text-center">
                                            Questions
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Correct
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Accuracy
                                        </TableHead>
                                        <TableHead>Mastery</TableHead>
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
                            <CardTitle>Recent Exams</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Exam</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Category</TableHead>
                                        <TableHead className="text-center">
                                            Score
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Result
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Date
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
