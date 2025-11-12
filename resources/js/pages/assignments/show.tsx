import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

const statusLabels: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' }> = {
    draft: { label: 'Draft', variant: 'secondary' },
    submitted: { label: 'Submitted', variant: 'default' },
    graded: { label: 'Graded', variant: 'default' },
    returned: { label: 'Returned', variant: 'secondary' },
};

export default function AssignmentShow({ assignment, submissions }: Props) {
    const submittedCount = submissions.filter(
        (s) => s.status === 'submitted',
    ).length;
    const gradedCount = submissions.filter((s) => s.status === 'graded').length;

    return (
        <AppLayout>
            <Head title={assignment.title} />

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/assignments">
                            <ArrowLeft className="mr-2 size-4" />
                            Back
                        </Link>
                    </Button>
                    <h1 className="mt-2 text-3xl font-bold">{assignment.title}</h1>
                    <div className="mt-2 flex items-center gap-2">
                        <Badge variant="outline">
                            {assignmentTypeLabels[assignment.assignment_type]}
                        </Badge>
                        <Badge
                            variant={
                                assignment.is_published ? 'default' : 'secondary'
                            }
                        >
                            {assignment.is_published ? 'Published' : 'Draft'}
                        </Badge>
                        {assignment.is_overdue && (
                            <Badge variant="destructive">Overdue</Badge>
                        )}
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Submissions
                            </CardTitle>
                            <FileText className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {submissions.length}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                From residents
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Pending Grading
                            </CardTitle>
                            <Eye className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{submittedCount}</div>
                            <p className="text-xs text-muted-foreground">
                                Awaiting review
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Graded
                            </CardTitle>
                            <Calendar className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{gradedCount}</div>
                            <p className="text-xs text-muted-foreground">
                                Completed reviews
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Assignment Details */}
                <Card>
                    <CardHeader>
                        <CardTitle>Assignment Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {assignment.description && (
                            <div>
                                <Label>Description</Label>
                                <p className="mt-1 text-sm">{assignment.description}</p>
                            </div>
                        )}

                        {assignment.instructions && (
                            <div>
                                <Label>Instructions</Label>
                                <p className="mt-1 whitespace-pre-wrap text-sm">
                                    {assignment.instructions}
                                </p>
                            </div>
                        )}

                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            {assignment.due_date && (
                                <div>
                                    <Label>Due Date</Label>
                                    <p className="mt-1 text-sm">
                                        {new Date(assignment.due_date).toLocaleString()}
                                    </p>
                                </div>
                            )}
                            <div>
                                <Label>Max Score</Label>
                                <p className="mt-1 text-sm">
                                    {assignment.max_score} points
                                </p>
                            </div>
                            {assignment.target_year_levels &&
                                assignment.target_year_levels.length > 0 && (
                                    <div>
                                        <Label>Target Year Levels</Label>
                                        <p className="mt-1 text-sm">
                                            {assignment.target_year_levels.join(', ')}
                                        </p>
                                    </div>
                                )}
                            <div>
                                <Label>Late Submission</Label>
                                <p className="mt-1 text-sm">
                                    {assignment.allow_late_submission
                                        ? `Allowed (${assignment.late_penalty_percent}% penalty)`
                                        : 'Not allowed'}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Submissions Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Resident Submissions ({submissions.length})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {submissions.length > 0 ? (
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
                                                {new Date(
                                                    submission.submitted_at,
                                                ).toLocaleDateString()}
                                                {submission.is_late && (
                                                    <Badge
                                                        variant="destructive"
                                                        className="ml-2"
                                                    >
                                                        Late ({submission.late_days}d)
                                                    </Badge>
                                                )}
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
                                                        —
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

