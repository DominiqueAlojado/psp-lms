import AppLayout from '@/layouts/app-layout';
import AssessmentReportsLayout from '@/layouts/assessment-reports/assessment-reports-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Award,
    CheckCircle2,
    TrendingDown,
    TrendingUp,
    XCircle,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

interface ResidentInfo {
    id: number;
    name: string;
    year_level: string;
    status: string;
    course: string;
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

interface Props {
    resident: ResidentInfo;
    stats: Stats;
    categoryPerformance: CategoryPerformance[];
    topicPerformance: TopicPerformance[];
    recentExams: RecentExam[];
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
    categoryPerformance,
    topicPerformance,
    recentExams,
    institutionAttempts,
    nationalAttempts,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${resident.name} - Performance Report`} />

            <AssessmentReportsLayout>
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href="/assessment-reports/by-performance">
                                        <ArrowLeft className="mr-2 size-4" />
                                        Back
                                    </Link>
                                </Button>
                            </div>
                            <h1 className="text-3xl font-bold">{resident.name}</h1>
                            <div className="flex items-center gap-2">
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
                                <span className="text-sm text-muted-foreground">
                                    {resident.course}
                                </span>
                            </div>
                        </div>
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
                                    {stats.total_passed} passed, {stats.total_failed}{' '}
                                    failed
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

                    {/* Tabs for different views */}
                    <Tabs defaultValue="overview" className="space-y-4">
                        <TabsList>
                            <TabsTrigger value="overview">Overview</TabsTrigger>
                            <TabsTrigger value="institution">
                                Institution Exams ({institutionAttempts.length})
                            </TabsTrigger>
                            <TabsTrigger value="national">
                                National Exams ({nationalAttempts.length})
                            </TabsTrigger>
                        </TabsList>

                        {/* Overview Tab */}
                        <TabsContent value="overview" className="space-y-4">
                            {/* Performance by Category */}
                            {categoryPerformance.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>
                                            Performance by Category
                                        </CardTitle>
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
                                                {categoryPerformance.map(
                                                    (category) => (
                                                        <TableRow
                                                            key={category.category}
                                                        >
                                                            <TableCell className="font-medium">
                                                                {category.category}
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {category.exam_count}
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {category.average_percentage.toFixed(
                                                                    1
                                                                )}
                                                                %
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {category.pass_rate.toFixed(
                                                                    1
                                                                )}
                                                                %
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
                                                    )
                                                )}
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
                                                                    topic.accuracy >=
                                                                    80
                                                                        ? 'default'
                                                                        : topic.accuracy >=
                                                                            60
                                                                          ? 'secondary'
                                                                          : 'destructive'
                                                                }
                                                            >
                                                                {topic.accuracy.toFixed(
                                                                    1
                                                                )}
                                                                %
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Progress
                                                                value={
                                                                    topic.accuracy
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
                        </TabsContent>

                        {/* Institution Exams Tab */}
                        <TabsContent value="institution">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Institution Exam History</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {institutionAttempts.length > 0 ? (
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Exam</TableHead>
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
                                                {institutionAttempts.map(
                                                    (attempt) => (
                                                        <TableRow key={attempt.id}>
                                                            <TableCell className="font-medium">
                                                                {attempt.exam_title}
                                                            </TableCell>
                                                            <TableCell>
                                                                {attempt.exam_category ||
                                                                    'N/A'}
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {attempt.score} /{' '}
                                                                {
                                                                    attempt.total_points
                                                                }{' '}
                                                                (
                                                                {attempt.percentage.toFixed(
                                                                    1
                                                                )}
                                                                %)
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {attempt.passed ? (
                                                                    <Badge className="gap-1 bg-green-500">
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
                                                                    attempt.submitted_at
                                                                ).toLocaleDateString()}
                                                            </TableCell>
                                                        </TableRow>
                                                    )
                                                )}
                                            </TableBody>
                                        </Table>
                                    ) : (
                                        <div className="flex flex-col items-center justify-center py-12">
                                            <Award className="mb-4 size-12 text-muted-foreground" />
                                            <h3 className="mb-2 text-lg font-semibold">
                                                No institution exams
                                            </h3>
                                            <p className="text-sm text-muted-foreground">
                                                This resident hasn't completed any
                                                institution exams yet
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* National Exams Tab */}
                        <TabsContent value="national">
                            <Card>
                                <CardHeader>
                                    <CardTitle>National Exam History</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {nationalAttempts.length > 0 ? (
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Exam</TableHead>
                                                    <TableHead className="text-center">
                                                        Score
                                                    </TableHead>
                                                    <TableHead className="text-center">
                                                        National Rank
                                                    </TableHead>
                                                    <TableHead className="text-center">
                                                        Institution Rank
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
                                                {nationalAttempts.map((attempt) => (
                                                    <TableRow key={attempt.id}>
                                                        <TableCell className="font-medium">
                                                            {attempt.exam_title}
                                                        </TableCell>
                                                        <TableCell className="text-center">
                                                            {attempt.score} /{' '}
                                                            {attempt.total_points} (
                                                            {attempt.percentage.toFixed(
                                                                1
                                                            )}
                                                            %)
                                                        </TableCell>
                                                        <TableCell className="text-center">
                                                            {attempt.national_rank ||
                                                                'N/A'}
                                                        </TableCell>
                                                        <TableCell className="text-center">
                                                            {attempt.institution_rank ||
                                                                'N/A'}
                                                        </TableCell>
                                                        <TableCell className="text-center">
                                                            {attempt.passed ? (
                                                                <Badge className="gap-1 bg-green-500">
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
                                                                attempt.submitted_at
                                                            ).toLocaleDateString()}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    ) : (
                                        <div className="flex flex-col items-center justify-center py-12">
                                            <Award className="mb-4 size-12 text-muted-foreground" />
                                            <h3 className="mb-2 text-lg font-semibold">
                                                No national exams
                                            </h3>
                                            <p className="text-sm text-muted-foreground">
                                                This resident hasn't completed any
                                                national exams yet
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

