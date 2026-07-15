import {
    SubmitAssignmentSheet,
    ViewSubmissionSheet,
} from '@/components/assignments';
import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
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
import { Head } from '@inertiajs/react';
import {
    AlertCircle,
    Calendar,
    CheckCircle2,
    Clock,
    FileText,
    Pencil,
} from 'lucide-react';
import { useState } from 'react';

interface SubmissionFile {
    id: number;
    original_name: string;
    file_path: string;
    file_size: number;
    mime_type: string;
}

interface SubmissionInfo {
    id: number;
    status: string;
    score: number | null;
    submitted_at: string;
    is_late: boolean;
    grader_feedback: string | null;
    files?: SubmissionFile[];
    submission_text?: string | null;
}

interface Assignment {
    id: number;
    title: string;
    description: string;
    instructions: string;
    assignment_type: string;
    due_date: string | null;
    max_score: number;
    allowed_file_types: string[];
    max_file_size_mb: number;
    max_files: number;
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

const statusLabels: Record<
    string,
    { label: string; variant: 'default' | 'secondary' | 'destructive' }
> = {
    draft: { label: 'Draft', variant: 'secondary' },
    submitted: { label: 'Submitted', variant: 'default' },
    graded: { label: 'Graded', variant: 'default' },
    returned: { label: 'Returned', variant: 'secondary' },
};

export default function MyAssignments({ assignments }: Props) {
    const [submitSheetOpen, setSubmitSheetOpen] = useState(false);
    const [viewSubmissionSheetOpen, setViewSubmissionSheetOpen] =
        useState(false);
    const [selectedAssignment, setSelectedAssignment] =
        useState<Assignment | null>(null);

    const pendingAssignments = assignments.filter(
        (assignment) => !assignment.has_submitted && assignment.can_still_submit,
    );
    const submittedAssignments = assignments.filter((assignment) => assignment.has_submitted);
    const overdueAssignments = assignments.filter(
        (assignment) => !assignment.has_submitted && assignment.is_overdue,
    );

    const handleSubmit = (assignment: Assignment) => {
        setSelectedAssignment(assignment);
        setSubmitSheetOpen(true);
    };

    const handleViewSubmission = (assignment: Assignment) => {
        setSelectedAssignment(assignment);
        setViewSubmissionSheetOpen(true);
    };

    return (
        <AppLayout>
            <Head title="My Assignments" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <HeadingSmall
                    title="My Assignments"
                    description="Submit case reports, logs, and required learning work from a single queue."
                />

                <div className="grid gap-4 md:grid-cols-3">
                    <StatCard
                        title="Pending"
                        value={pendingAssignments.length}
                        description="Awaiting submission"
                        icon={Clock}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Submitted"
                        value={submittedAssignments.length}
                        description="Completed assignments"
                        icon={CheckCircle2}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Overdue"
                        value={overdueAssignments.length}
                        description="Past deadline and needing attention"
                        icon={AlertCircle}
                        iconColor="text-primary"
                    />
                </div>

                <Card className="overflow-hidden border-primary/10 bg-[linear-gradient(135deg,rgba(248,244,255,0.98),rgba(255,255,255,0.94))]">
                    <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Submission snapshot
                            </p>
                            <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                Keep your assignment queue under control
                            </h3>
                            <p className="text-sm leading-6 text-muted-foreground">
                                Review deadlines, submission state, and grading outcomes before anything slips.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">
                                {pendingAssignments.length} pending
                            </Badge>
                            <Badge variant="outline">
                                {submittedAssignments.length} submitted
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                    <CardHeader className="pb-3">
                        <CardTitle>
                            All Assignments ({assignments.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {assignments.length > 0 ? (
                            <div className="overflow-x-auto">
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
                                                        {assignment.description ? (
                                                            <div className="mt-1 line-clamp-1 text-sm text-muted-foreground">
                                                                {assignment.description}
                                                            </div>
                                                        ) : null}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">
                                                        {assignmentTypeLabels[assignment.assignment_type]}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {assignment.due_date ? (
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <Calendar className="size-3" />
                                                            {new Date(
                                                                assignment.due_date,
                                                            ).toLocaleDateString()}
                                                            {assignment.is_overdue &&
                                                            !assignment.has_submitted ? (
                                                                <Badge variant="destructive">
                                                                    Overdue
                                                                </Badge>
                                                            ) : null}
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
                                                                    assignment.submission.status
                                                                ]?.variant || 'secondary'
                                                            }
                                                        >
                                                            {statusLabels[
                                                                assignment.submission.status
                                                            ]?.label || assignment.submission.status}
                                                            {assignment.submission.is_late
                                                                ? ' (Late)'
                                                                : ''}
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="secondary">
                                                            Not Submitted
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {assignment.submission?.score !== null &&
                                                    assignment.submission?.score !== undefined ? (
                                                        <span className="font-medium">
                                                            {assignment.submission.score} /{' '}
                                                            {assignment.max_score}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            No score
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {!assignment.has_submitted &&
                                                    assignment.can_still_submit ? (
                                                        <Button
                                                            size="sm"
                                                            onClick={() =>
                                                                handleSubmit(assignment)
                                                            }
                                                        >
                                                            <FileText className="mr-2 size-4" />
                                                            Submit
                                                        </Button>
                                                    ) : assignment.allow_resubmission &&
                                                      assignment.submission_count <
                                                          assignment.max_submissions ? (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                handleSubmit(assignment)
                                                            }
                                                        >
                                                            <FileText className="mr-2 size-4" />
                                                            Resubmit
                                                        </Button>
                                                    ) : assignment.submission ? (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                handleViewSubmission(assignment)
                                                            }
                                                        >
                                                            <FileText className="mr-2 size-4" />
                                                            View
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
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12">
                                <Pencil className="mb-4 size-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-semibold">
                                    No assignments yet
                                </h3>
                                <p className="text-center text-sm text-muted-foreground">
                                    Assignments from your training officers will appear here
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <SubmitAssignmentSheet
                open={submitSheetOpen}
                assignment={selectedAssignment}
                onClose={() => setSubmitSheetOpen(false)}
            />

            <ViewSubmissionSheet
                open={viewSubmissionSheetOpen}
                assignment={selectedAssignment}
                onClose={() => setViewSubmissionSheetOpen(false)}
            />
        </AppLayout>
    );
}
