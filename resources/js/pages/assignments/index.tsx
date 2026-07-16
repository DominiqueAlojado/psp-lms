import {
    CreateAssignmentSheet,
    EditAssignmentSheet,
    ViewAssignmentSheet,
    AssignmentLogsSheet,
} from '@/components/assignments';
import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
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
import { Head, router } from '@inertiajs/react';
import {
    Calendar,
    CheckCircle2,
    Eye,
    FileText,
    Pencil,
    Plus,
    SquarePen,
    TimerReset,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

interface Assignment {
    id: number;
    title: string;
    assignment_type: string;
    description?: string | null;
    instructions?: string | null;
    target_year_levels?: string[] | null;
    max_score?: number;
    due_date: string | null;
    allow_late_submission?: boolean;
    late_submission_until?: string | null;
    late_penalty_percent?: number;
    allow_resubmission?: boolean;
    max_submissions?: number;
    allowed_file_types?: string[];
    max_file_size_mb?: number;
    max_files?: number;
    is_published: boolean;
    is_overdue: boolean;
    submissions_count: number;
    graded_count: number;
    created_by: string;
    created_at: string;
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

interface SubmissionFile {
    id: number;
    original_name: string;
    file_path: string;
    file_size: number;
    mime_type: string;
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
    submission_text?: string | null;
    files?: SubmissionFile[];
}

export default function AssignmentsIndex({ assignments }: Props) {
    const [createSheetOpen, setCreateSheetOpen] = useState(false);
    const [editSheetOpen, setEditSheetOpen] = useState(false);
    const [viewSheetOpen, setViewSheetOpen] = useState(false);
    const [selectedAssignment, setSelectedAssignment] =
        useState<Assignment | null>(null);
    const [submissions, setSubmissions] = useState<Submission[]>([]);
    const [viewingLogsAssignment, setViewingLogsAssignment] =
        useState<Assignment | null>(null);
    const [showLogsSheet, setShowLogsSheet] = useState(false);
    const publishedAssignments = assignments.filter((assignment) => assignment.is_published);
    const overdueAssignments = assignments.filter((assignment) => assignment.is_overdue);
    const totalSubmissions = assignments.reduce(
        (total, assignment) => total + assignment.submissions_count,
        0,
    );
    const gradedSubmissions = assignments.reduce(
        (total, assignment) => total + assignment.graded_count,
        0,
    );

    const handleDelete = (id: number) => {
        router.delete(`/assignments/${id}`, {
            onSuccess: () => {
                // Success toast handled by Inertia
            },
        });
    };

    const fetchSubmissions = async (assignmentId: number) => {
        try {
            const response = await fetch(
                `/api/assignments/${assignmentId}/submissions`,
            );
            if (response.ok) {
                const data = await response.json();
                setSubmissions(data);
            }
        } catch (error) {
            console.error('Failed to fetch submissions:', error);
        }
    };

    const handleView = async (assignment: Assignment) => {
        setSelectedAssignment(assignment);
        setSubmissions([]); // Reset submissions
        setViewSheetOpen(true);

        // Fetch submissions for this assignment
        await fetchSubmissions(assignment.id);
    };

    const handleRefreshSubmissions = async () => {
        if (selectedAssignment) {
            await fetchSubmissions(selectedAssignment.id);
        }
    };

    const handleEdit = (assignment: Assignment) => {
        setSelectedAssignment(assignment);
        setEditSheetOpen(true);
    };

    const handleViewLogs = (assignment: Assignment) => {
        setViewingLogsAssignment(assignment);
        setShowLogsSheet(true);
    };

    return (
        <AppLayout>
            <Head title="Assignments" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <HeadingSmall
                        title="Assignments"
                        description="Create, publish, and monitor assignment workflows for residents."
                    />
                    <Button onClick={() => setCreateSheetOpen(true)}>
                        <Plus className="mr-2 size-4" />
                        Create Assignment
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Assignments"
                        value={assignments.length}
                        description="Total assignment records in this workspace"
                        icon={FileText}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Published"
                        value={publishedAssignments.length}
                        description="Assignments currently visible to residents"
                        icon={CheckCircle2}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Submissions"
                        value={totalSubmissions}
                        description="Resident submissions received across all assignments"
                        icon={TimerReset}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Graded"
                        value={gradedSubmissions}
                        description={`${overdueAssignments.length} overdue assignment${overdueAssignments.length === 1 ? '' : 's'}`}
                        icon={Calendar}
                        iconColor="text-primary"
                    />
                </div>

                <Card className="overflow-hidden border-primary/12 bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_88%,white)_0%,color-mix(in_oklab,var(--color-card)_96%,var(--color-accent))_100%)] dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_72%,black)_0%,color-mix(in_oklab,var(--color-card)_92%,var(--color-accent))_100%)]">
                    <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Workflow overview
                            </p>
                            <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                Assignment operations at a glance
                            </h3>
                            <p className="text-sm leading-6 text-muted-foreground">
                                Track publication state, upcoming deadlines, and grading throughput from one place.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">
                                {publishedAssignments.length} published
                            </Badge>
                            <Badge variant="outline">
                                {overdueAssignments.length} overdue
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
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
                                        <TableHead>Title</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Due Date</TableHead>
                                        <TableHead className="text-center">
                                            Status
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Submissions
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Graded
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {assignments.map((assignment) => (
                                        <TableRow key={assignment.id}>
                                            <TableCell className="font-medium">
                                                <div className="space-y-1">
                                                    <p>{assignment.title}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        Created by {assignment.created_by}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {
                                                        assignmentTypeLabels[
                                                            assignment
                                                                .assignment_type
                                                        ]
                                                    }
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {assignment.due_date ? (
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Calendar className="size-3" />
                                                        {new Date(
                                                            assignment.due_date,
                                                        ).toLocaleDateString()}
                                                        {assignment.is_overdue && (
                                                            <Badge
                                                                variant="destructive"
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
                                                <Badge
                                                    variant={
                                                        assignment.is_published
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {assignment.is_published
                                                        ? 'Published'
                                                        : 'Draft'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {assignment.submissions_count}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {assignment.graded_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            handleView(
                                                                assignment,
                                                            )
                                                        }
                                                        title="View"
                                                    >
                                                        <Eye className="size-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            handleViewLogs(
                                                                assignment,
                                                            )
                                                        }
                                                        title="View Activity Logs"
                                                    >
                                                        <FileText className="size-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            handleEdit(
                                                                assignment,
                                                            )
                                                        }
                                                        title="Edit"
                                                    >
                                                        <SquarePen className="size-4" />
                                                    </Button>
                                                    <AlertDialog>
                                                        <AlertDialogTrigger
                                                            asChild
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                            >
                                                                <Trash2 className="size-4 text-destructive" />
                                                            </Button>
                                                        </AlertDialogTrigger>
                                                        <AlertDialogContent>
                                                            <AlertDialogHeader>
                                                                <AlertDialogTitle>
                                                                    Delete
                                                                    Assignment?
                                                                </AlertDialogTitle>
                                                                <AlertDialogDescription>
                                                                    This will
                                                                    permanently
                                                                    delete the
                                                                    assignment
                                                                    and all
                                                                    submissions.
                                                                    This action
                                                                    cannot be
                                                                    undone.
                                                                </AlertDialogDescription>
                                                            </AlertDialogHeader>
                                                            <AlertDialogFooter>
                                                                <AlertDialogCancel>
                                                                    Cancel
                                                                </AlertDialogCancel>
                                                                <AlertDialogAction
                                                                    onClick={() =>
                                                                        handleDelete(
                                                                            assignment.id,
                                                                        )
                                                                    }
                                                                >
                                                                    Delete
                                                                </AlertDialogAction>
                                                            </AlertDialogFooter>
                                                        </AlertDialogContent>
                                                    </AlertDialog>
                                                </div>
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
                                <p className="mb-4 text-center text-sm text-muted-foreground">
                                    Create your first assignment to get started
                                </p>
                                <Button
                                    onClick={() => setCreateSheetOpen(true)}
                                >
                                    <Plus className="mr-2 size-4" />
                                    Create Assignment
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Create Assignment Sheet */}
            <CreateAssignmentSheet
                open={createSheetOpen}
                onClose={() => setCreateSheetOpen(false)}
            />

            {/* View Assignment Sheet */}
            <ViewAssignmentSheet
                open={viewSheetOpen}
                assignment={selectedAssignment}
                submissions={submissions}
                onClose={() => setViewSheetOpen(false)}
                onEdit={handleEdit}
                onRefreshSubmissions={handleRefreshSubmissions}
            />

            {/* Edit Assignment Sheet */}
            <EditAssignmentSheet
                open={editSheetOpen}
                assignment={selectedAssignment}
                onClose={() => setEditSheetOpen(false)}
            />

            {/* Assignment Logs Sheet */}
            {viewingLogsAssignment && (
                <AssignmentLogsSheet
                    open={showLogsSheet}
                    onOpenChange={(open) => {
                        setShowLogsSheet(open);
                        if (!open) {
                            setViewingLogsAssignment(null);
                        }
                    }}
                    assignment={viewingLogsAssignment}
                />
            )}
        </AppLayout>
    );
}
