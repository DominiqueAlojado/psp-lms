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
import {
    AlertCircle,
    Calendar,
    CheckCircle2,
    Clock,
    FileText,
    Pencil,
} from 'lucide-react';

interface SubmissionInfo {
    id: number;
    status: string;
    score: number | null;
    submitted_at: string;
    is_late: boolean;
    grader_feedback: string | null;
}

interface Assignment {
    id: number;
    title: string;
    description: string;
    assignment_type: string;
    due_date: string | null;
    max_score: number;
    is_overdue: boolean;
    can_still_submit: boolean;
    has_submitted: boolean;
    submission_count: number;
    max_submissions: number;
    allow_resubmission: boolean;
    submission: SubmissionInfo | null;
}

interface Props {
    assignments: Assignment[];
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

export default function MyAssignments({ assignments }: Props) {
    const pendingAssignments = assignments.filter(
        (a) => !a.has_submitted && a.can_still_submit,
    );
    const submittedAssignments = assignments.filter((a) => a.has_submitted);
    const overdueAssignments = assignments.filter(
        (a) => !a.has_submitted && a.is_overdue,
    );

    return (
        <AppLayout>
            <Head title="My Assignments" />

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold">My Assignments</h1>
                    <p className="text-muted-foreground">
                        Submit case reports, logs, and other required assignments
                    </p>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Pending
                            </CardTitle>
                            <Clock className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {pendingAssignments.length}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Awaiting submission
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Submitted
                            </CardTitle>
                            <CheckCircle2 className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {submittedAssignments.length}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Completed assignments
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Overdue
                            </CardTitle>
                            <AlertCircle className="size-4 text-destructive" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-destructive">
                                {overdueAssignments.length}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Past deadline
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Assignments Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>All Assignments ({assignments.length})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {assignments.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Assignment</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Due Date</TableHead>
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
                                    {assignments.map((assignment) => (
                                        <TableRow key={assignment.id}>
                                            <TableCell>
                                                <div>
                                                    <div className="font-medium">
                                                        {assignment.title}
                                                    </div>
                                                    {assignment.description && (
                                                        <div className="mt-1 text-sm text-muted-foreground line-clamp-1">
                                                            {assignment.description}
                                                        </div>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {assignmentTypeLabels[assignment.assignment_type]}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {assignment.due_date ? (
                                                    <div className="flex items-center gap-1">
                                                        <Calendar className="size-3" />
                                                        {new Date(
                                                            assignment.due_date,
                                                        ).toLocaleDateString()}
                                                        {assignment.is_overdue &&
                                                            !assignment.has_submitted && (
                                                                <Badge
                                                                    variant="destructive"
                                                                    className="ml-2"
                                                                >
                                                                    Overdue
                                                                </Badge>
                                                            )}
                                                    </div>
                                                ) : (
                                                    'No deadline'
                                                )}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {assignment.submission ? (
                                                    <Badge
                                                        variant={
                                                            statusLabels[
                                                                assignment.submission
                                                                    .status
                                                            ]?.variant || 'secondary'
                                                        }
                                                    >
                                                        {statusLabels[
                                                            assignment.submission
                                                                .status
                                                        ]?.label || assignment.submission.status}
                                                        {assignment.submission.is_late && ' (Late)'}
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="secondary">
                                                        Not Submitted
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {assignment.submission?.score ? (
                                                    <span className="font-medium">
                                                        {assignment.submission.score} /{' '}
                                                        {assignment.max_score}
                                                    </span>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {!assignment.has_submitted &&
                                                assignment.can_still_submit ? (
                                                    <Button size="sm" asChild>
                                                        <Link
                                                            href={`/assignments/${assignment.id}/submit`}
                                                        >
                                                            <FileText className="mr-2 size-4" />
                                                            Submit
                                                        </Link>
                                                    </Button>
                                                ) : assignment.allow_resubmission &&
                                                  assignment.submission_count <
                                                      assignment.max_submissions ? (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/assignments/${assignment.id}/submit`}
                                                        >
                                                            <FileText className="mr-2 size-4" />
                                                            Resubmit
                                                        </Link>
                                                    </Button>
                                                ) : assignment.submission ? (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        disabled
                                                    >
                                                        Submitted
                                                    </Button>
                                                ) : (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        disabled
                                                    >
                                                        Closed
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12">
                                <Pencil className="mb-4 size-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-semibold">
                                    No assignments yet
                                </h3>
                                <p className="text-center text-sm text-muted-foreground">
                                    Assignments from your training officers will appear
                                    here
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

