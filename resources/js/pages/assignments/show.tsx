import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Calendar, Eye, FileText } from 'lucide-react';

interface Assignment {
    id: number;
    title: string;
    description: string;
    instructions: string;
    assignment_type: string;
    target_year_levels: string[] | null;
    max_score: number;
    due_date: string | null;
    allow_late_submission: boolean;
    late_submission_until: string | null;
    late_penalty_percent: number;
    allow_resubmission: boolean;
    max_submissions: number;
    allowed_file_types: string[];
    max_file_size_mb: number;
    max_files: number;
    is_published: boolean;
    is_overdue: boolean;
    can_still_submit: boolean;
    created_by: string;
    created_at: string;
}

interface Submission {
    id: number;
    resident_name: string;
    year_level: string;
    submitted_at: string;
    status: string;
    score: number | null;
    max_score: number;
    percentage: number | null;
    is_late: boolean;
    late_days: number;
    files_count: number;
    has_feedback: boolean;
}

interface Props {
    assignment: Assignment;
    submissions: Submission[];
}

const assignmentTypeLabels: Record<string, string> = {
    case_report: 'Case Report',
    procedure_log: 'Procedure Log',
    journal_review: 'Journal Review',
    presentation: 'Presentation',
    research_paper: 'Research Paper',
    reflection: 'Reflection',
    other: 'Other',
};

const statusLabels: Record<
    string,
    { label: string; variant: 'default' | 'secondary' | 'destructive' }
> = {
    draft: { label: 'Draft', variant: 'secondary' },
    submitted: { label: 'Submitted', variant: 'default' },
    graded: { label: 'Graded', variant: 'default' },
    returned: { label: 'Returned', variant: 'secondary' },
};

export default function AssignmentShow({ assignment, submissions }: Props) {
    const submittedCount = submissions.filter(
        (submission) => submission.status === 'submitted',
    ).length;
    const gradedCount = submissions.filter(
        (submission) => submission.status === 'graded',
    ).length;

    return (
        <AppLayout>
            <Head title={assignment.title} />

            <div className="space-y-6 p-6">
                <div className="space-y-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/assignments">
                            <ArrowLeft className="mr-2 size-4" />
                            Back
                        </Link>
                    </Button>
                    <HeadingSmall
                        title={assignment.title}
                        description="Review assignment details, publication state, and resident submission progress."
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline">
                            {assignmentTypeLabels[assignment.assignment_type]}
                        </Badge>
                        <Badge
                            variant={assignment.is_published ? 'default' : 'secondary'}
                        >
                            {assignment.is_published ? 'Published' : 'Draft'}
                        </Badge>
                        {assignment.is_overdue ? (
                            <Badge variant="destructive">Overdue</Badge>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <StatCard
                        title="Total Submissions"
                        value={submissions.length}
                        description="Resident submissions received so far"
                        icon={FileText}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Pending Grading"
                        value={submittedCount}
                        description="Submissions awaiting review"
                        icon={Eye}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Graded"
                        value={gradedCount}
                        description="Completed grading decisions"
                        icon={Calendar}
                        iconColor="text-primary"
                    />
                </div>

                <Card className="overflow-hidden border-primary/10 bg-[linear-gradient(135deg,rgba(248,244,255,0.98),rgba(255,255,255,0.94))]">
                    <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Assignment brief
                            </p>
                            <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                Submission requirements and grading settings
                            </h3>
                            <p className="text-sm leading-6 text-muted-foreground">
                                Confirm scope, deadline behavior, and target learners before grading the responses below.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">
                                {submissions.length} submissions
                            </Badge>
                            <Badge variant="outline">
                                {gradedCount} graded
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                    <CardHeader className="pb-3">
                        <CardTitle>Assignment Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {assignment.description ? (
                            <div>
                                <Label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                    Description
                                </Label>
                                <p className="mt-1 text-sm">{assignment.description}</p>
                            </div>
                        ) : null}

                        {assignment.instructions ? (
                            <div>
                                <Label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                    Instructions
                                </Label>
                                <p className="mt-1 whitespace-pre-wrap text-sm">
                                    {assignment.instructions}
                                </p>
                            </div>
                        ) : null}

                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            {assignment.due_date ? (
                                <div>
                                    <Label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                        Due Date
                                    </Label>
                                    <p className="mt-1 text-sm">
                                        {new Date(assignment.due_date).toLocaleString()}
                                    </p>
                                </div>
                            ) : null}
                            <div>
                                <Label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                    Max Score
                                </Label>
                                <p className="mt-1 text-sm">
                                    {assignment.max_score} points
                                </p>
                            </div>
                            {assignment.target_year_levels &&
                            assignment.target_year_levels.length > 0 ? (
                                <div>
                                    <Label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                        Target Year Levels
                                    </Label>
                                    <p className="mt-1 text-sm">
                                        {assignment.target_year_levels.join(', ')}
                                    </p>
                                </div>
                            ) : null}
                            <div>
                                <Label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                    Late Submission
                                </Label>
                                <p className="mt-1 text-sm">
                                    {assignment.allow_late_submission
                                        ? `Allowed (${assignment.late_penalty_percent}% penalty)`
                                        : 'Not allowed'}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                    <CardHeader className="pb-3">
                        <CardTitle>Resident Submissions ({submissions.length})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {submissions.length > 0 ? (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Resident</TableHead>
                                            <TableHead>Year Level</TableHead>
                                            <TableHead>Submitted</TableHead>
                                            <TableHead className="text-center">
                                                Files
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Status
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Score
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Actions
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {submissions.map((submission) => (
                                            <TableRow key={submission.id}>
                                                <TableCell className="font-medium">
                                                    {submission.resident_name}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">
                                                        {submission.year_level}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        {new Date(
                                                            submission.submitted_at,
                                                        ).toLocaleDateString()}
                                                        {submission.is_late ? (
                                                            <Badge variant="destructive">
                                                                Late ({submission.late_days}d)
                                                            </Badge>
                                                        ) : null}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {submission.files_count}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Badge
                                                        variant={
                                                            statusLabels[submission.status]
                                                                ?.variant || 'secondary'
                                                        }
                                                    >
                                                        {statusLabels[submission.status]
                                                            ?.label || submission.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {submission.score !== null ? (
                                                        <span className="font-medium">
                                                            {submission.score} /{' '}
                                                            {submission.max_score}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            No score
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/submissions/${submission.id}/grade`}
                                                        >
                                                            <Eye className="mr-2 size-4" />
                                                            {submission.status === 'graded'
                                                                ? 'View'
                                                                : 'Grade'}
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12">
                                <FileText className="mb-4 size-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-semibold">
                                    No submissions yet
                                </h3>
                                <p className="text-center text-sm text-muted-foreground">
                                    Submissions will appear here once residents submit their work
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
