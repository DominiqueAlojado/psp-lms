import HeadingSmall from '@/components/heading-small';
import { preserveOrgParam } from '@/lib/utils';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import AssessmentReportsLayout from '@/layouts/assessment-reports/assessment-reports-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Award,
    CheckCircle2,
    TrendingDown,
    TrendingUp,
    XCircle,
} from 'lucide-react';

interface ResidentInfo {
    id: number;
    name: string;
    year_level: string;
    status: string;
    course: string;
    organization_name?: string | null;
}

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

interface InstitutionAttempt {
    id: number;
    type: string;
    exam_title: string;
    exam_category: string | null;
    score: number;
    total_points: number;
    percentage: number;
    passing_score: number;
    passed: boolean;
    submitted_at: string;
}

interface NationalAttempt {
    id: number;
    type: string;
    exam_title: string;
    exam_year: number;
    score: number;
    total_points: number;
    percentage: number;
    passing_score: number;
    passed: boolean;
    national_rank: number | null;
    institution_rank: number | null;
    percentile: number | null;
    submitted_at: string;
}

interface ComparisonResident {
    resident_id: number;
    name: string;
    average_percentage: number;
    year_level?: string;
}

interface Comparison {
    organization_name: string | null;
    resident_average_percentage: number;
    same_year_level_average_percentage: number;
    organization_average_percentage: number;
    same_year_level_gap: number;
    organization_gap: number;
    same_year_level_rank: number | null;
    same_year_level_total: number;
    organization_rank: number | null;
    organization_total: number;
    top_same_year_level_residents: ComparisonResident[];
    top_organization_residents: ComparisonResident[];
}

interface Props {
    resident: ResidentInfo;
    stats: Stats;
    comparison: Comparison;
    categoryPerformance: CategoryPerformance[];
    topicPerformance: TopicPerformance[];
    institutionAttempts: InstitutionAttempt[];
    nationalAttempts: NationalAttempt[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Assessment Reports',
        href: '/assessment-reports/by-performance',
    },
];

export default function ResidentDetailReport({
    resident,
    stats,
    comparison,
    categoryPerformance,
    topicPerformance,
    institutionAttempts,
    nationalAttempts,
}: Props) {
    const { auth } = usePage<SharedData>().props;
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${resident.name} - Performance Report`} />

            <AssessmentReportsLayout>
                <div className="space-y-6 p-6">
                    <div className="space-y-4">
                        <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={preserveOrgParam(
                                    '/assessment-reports/by-performance',
                                    auth.currentOrganization?.slug,
                                )}
                            >
                                <ArrowLeft className="mr-2 size-4" />
                                Back
                            </Link>
                        </Button>
                        <HeadingSmall
                            title={resident.name}
                            description="Detailed resident performance trends across institutional and national exams."
                        />
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="outline">{resident.year_level}</Badge>
                            <Badge
                                variant={
                                    resident.status === 'active'
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {resident.status}
                            </Badge>
                            <Badge variant="secondary">{resident.course}</Badge>
                            {resident.organization_name ? (
                                <Badge variant="outline">
                                    {resident.organization_name}
                                </Badge>
                            ) : null}
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <StatCard
                            title="Total Exams"
                            value={stats.total_exams}
                            description={`${stats.total_institution_exams} institution, ${stats.total_national_exams} national`}
                            icon={Award}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Average Score"
                            value={`${stats.average_percentage.toFixed(1)}%`}
                            description="Across all completed exams"
                            icon={TrendingUp}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Pass Rate"
                            value={`${stats.pass_rate.toFixed(1)}%`}
                            description={`${stats.total_passed} passed, ${stats.total_failed} failed`}
                            icon={CheckCircle2}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Score Range"
                            value={`${stats.lowest_score.toFixed(0)}% - ${stats.highest_score.toFixed(0)}%`}
                            description="Lowest to highest score"
                            icon={TrendingDown}
                            iconColor="text-primary"
                        />
                    </div>

                    <Card className="overflow-hidden border-primary/12 bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_88%,white)_0%,color-mix(in_oklab,var(--color-card)_96%,var(--color-accent))_100%)] dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_72%,black)_0%,color-mix(in_oklab,var(--color-card)_92%,var(--color-accent))_100%)]">
                        <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="space-y-1">
                                <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                    Resident focus
                                </p>
                                <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                    Performance by category, topic, and exam history
                                </h3>
                                <p className="text-sm leading-6 text-muted-foreground">
                                    Review mastery areas, weak spots, and historical exam results in one consolidated report.
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Badge variant="secondary">
                                    {categoryPerformance.length} categories
                                </Badge>
                                <Badge variant="outline">
                                    {topicPerformance.length} topics
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
                        <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                            <CardHeader className="pb-3">
                                <CardTitle>Peer Comparison</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                            Resident
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {comparison.resident_average_percentage.toFixed(1)}%
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Overall average
                                        </p>
                                    </div>
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                            Same Level
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {comparison.same_year_level_average_percentage.toFixed(1)}%
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {comparison.same_year_level_rank
                                                ? `Rank ${comparison.same_year_level_rank} of ${comparison.same_year_level_total}`
                                                : 'No same-level cohort data'}
                                        </p>
                                    </div>
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                            All Residents
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
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <p className="text-sm font-medium">
                                            Versus same year level
                                        </p>
                                        <p className="mt-2 text-xl font-semibold">
                                            {comparison.same_year_level_gap >= 0 ? '+' : ''}
                                            {comparison.same_year_level_gap.toFixed(1)} pts
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Difference from the {resident.year_level.toLowerCase()} average
                                        </p>
                                    </div>
                                    <div className="rounded-xl border border-border/70 bg-background/80 p-4">
                                        <p className="text-sm font-medium">
                                            Versus all residents
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
                            </CardContent>
                        </Card>

                        <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                            <CardHeader className="pb-3">
                                <CardTitle>Cohort Leaders</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-3">
                                    <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                        Same year level
                                    </p>
                                    {comparison.top_same_year_level_residents.map((peer, index) => (
                                        <div
                                            key={`same-level-${peer.resident_id}`}
                                            className="flex items-center justify-between rounded-xl border border-border/70 bg-background/80 px-4 py-3"
                                        >
                                            <div>
                                                <p className="font-medium">{index + 1}. {peer.name}</p>
                                            </div>
                                            <Badge variant="secondary">
                                                {peer.average_percentage.toFixed(1)}%
                                            </Badge>
                                        </div>
                                    ))}
                                </div>

                                <div className="space-y-3">
                                    <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                        Overall
                                    </p>
                                    {comparison.top_organization_residents.map((peer, index) => (
                                        <div
                                            key={`org-${peer.resident_id}`}
                                            className="flex items-center justify-between rounded-xl border border-border/70 bg-background/80 px-4 py-3"
                                        >
                                            <div>
                                                <p className="font-medium">{index + 1}. {peer.name}</p>
                                                {peer.year_level ? (
                                                    <p className="text-xs text-muted-foreground">
                                                        {peer.year_level}
                                                    </p>
                                                ) : null}
                                            </div>
                                            <Badge variant="secondary">
                                                {peer.average_percentage.toFixed(1)}%
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <Tabs defaultValue="overview" className="space-y-4">
                        <TabsList className="grid w-full grid-cols-3 lg:w-fit lg:min-w-[34rem]">
                            <TabsTrigger value="overview">Overview</TabsTrigger>
                            <TabsTrigger value="institution">
                                Institution Exams ({institutionAttempts.length})
                            </TabsTrigger>
                            <TabsTrigger value="national">
                                National Exams ({nationalAttempts.length})
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="overview" className="space-y-4">
                            {categoryPerformance.length > 0 ? (
                                <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                                    <CardHeader className="pb-3">
                                        <CardTitle>Performance by Category</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>Category</TableHead>
                                                        <TableHead className="text-center">Exams</TableHead>
                                                        <TableHead className="text-center">Average</TableHead>
                                                        <TableHead className="text-center">Pass Rate</TableHead>
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
                                                                {category.average_percentage.toFixed(1)}%
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {category.pass_rate.toFixed(1)}%
                                                            </TableCell>
                                                            <TableCell>
                                                                <Progress value={category.average_percentage} className="h-2" />
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </CardContent>
                                </Card>
                            ) : null}

                            {topicPerformance.length > 0 ? (
                                <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                                    <CardHeader className="pb-3">
                                        <CardTitle>Performance by Topic</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>Topic</TableHead>
                                                        <TableHead className="text-center">Questions</TableHead>
                                                        <TableHead className="text-center">Correct</TableHead>
                                                        <TableHead className="text-center">Accuracy</TableHead>
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
                                                                            : topic.accuracy >= 60
                                                                              ? 'secondary'
                                                                              : 'destructive'
                                                                    }
                                                                >
                                                                    {topic.accuracy.toFixed(1)}%
                                                                </Badge>
                                                            </TableCell>
                                                            <TableCell>
                                                                <Progress value={topic.accuracy} className="h-2" />
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </CardContent>
                                </Card>
                            ) : null}
                        </TabsContent>

                        <TabsContent value="institution">
                            <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                                <CardHeader className="pb-3">
                                    <CardTitle>Institution Exam History</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {institutionAttempts.length > 0 ? (
                                        <div className="overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>Exam</TableHead>
                                                        <TableHead>Category</TableHead>
                                                        <TableHead className="text-center">Score</TableHead>
                                                        <TableHead className="text-center">Result</TableHead>
                                                        <TableHead className="text-right">Date</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {institutionAttempts.map((attempt) => (
                                                        <TableRow key={attempt.id}>
                                                            <TableCell className="font-medium">
                                                                {attempt.exam_title}
                                                            </TableCell>
                                                            <TableCell>
                                                                {attempt.exam_category || 'N/A'}
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {attempt.score} / {attempt.total_points} ({attempt.percentage.toFixed(1)}%)
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {attempt.passed ? (
                                                                    <Badge className="gap-1 bg-green-500">
                                                                        <CheckCircle2 className="size-3" />
                                                                        Passed
                                                                    </Badge>
                                                                ) : (
                                                                    <Badge variant="destructive" className="gap-1">
                                                                        <XCircle className="size-3" />
                                                                        Failed
                                                                    </Badge>
                                                                )}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {new Date(attempt.submitted_at).toLocaleDateString()}
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    ) : (
                                        <div className="flex flex-col items-center justify-center py-12">
                                            <Award className="mb-4 size-12 text-muted-foreground" />
                                            <h3 className="mb-2 text-lg font-semibold">
                                                No institution exams
                                            </h3>
                                            <p className="text-sm text-muted-foreground">
                                                This resident hasn&apos;t completed any institution exams yet
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="national">
                            <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                                <CardHeader className="pb-3">
                                    <CardTitle>National Exam History</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {nationalAttempts.length > 0 ? (
                                        <div className="overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>Exam</TableHead>
                                                        <TableHead className="text-center">Score</TableHead>
                                                        <TableHead className="text-center">National Rank</TableHead>
                                                        <TableHead className="text-center">Institution Rank</TableHead>
                                                        <TableHead className="text-center">Result</TableHead>
                                                        <TableHead className="text-right">Date</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {nationalAttempts.map((attempt) => (
                                                        <TableRow key={attempt.id}>
                                                            <TableCell className="font-medium">
                                                                {attempt.exam_title}
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {attempt.score} / {attempt.total_points} ({attempt.percentage.toFixed(1)}%)
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {attempt.national_rank || 'N/A'}
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {attempt.institution_rank || 'N/A'}
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {attempt.passed ? (
                                                                    <Badge className="gap-1 bg-green-500">
                                                                        <CheckCircle2 className="size-3" />
                                                                        Passed
                                                                    </Badge>
                                                                ) : (
                                                                    <Badge variant="destructive" className="gap-1">
                                                                        <XCircle className="size-3" />
                                                                        Failed
                                                                    </Badge>
                                                                )}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {new Date(attempt.submitted_at).toLocaleDateString()}
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    ) : (
                                        <div className="flex flex-col items-center justify-center py-12">
                                            <Award className="mb-4 size-12 text-muted-foreground" />
                                            <h3 className="mb-2 text-lg font-semibold">
                                                No national exams
                                            </h3>
                                            <p className="text-sm text-muted-foreground">
                                                This resident hasn&apos;t completed any national exams yet
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </AssessmentReportsLayout>
        </AppLayout>
    );
}
