import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Calendar, Download, FileText, Users } from 'lucide-react';
import { useState } from 'react';
import { GradeSubmissionSheet } from './grade-submission-sheet';

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

interface Props {
    open: boolean;
    assignment: Assignment | null;
    submissions?: Submission[];
    onClose: () => void;
    onEdit?: (assignment: Assignment) => void;
    onViewSubmission?: (submissionId: number) => void;
    onRefreshSubmissions?: () => void;
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

const fileTypeLabels: Record<string, string> = {
    pdf: 'PDF',
    doc: 'Word (DOC)',
    docx: 'Word (DOCX)',
    ppt: 'PowerPoint (PPT)',
    pptx: 'PowerPoint (PPTX)',
    jpg: 'Image (JPG)',
    jpeg: 'Image (JPEG)',
    png: 'Image (PNG)',
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

export function ViewAssignmentSheet({
    open,
    assignment,
    submissions,
    onClose,
    onEdit,
    onViewSubmission,
    onRefreshSubmissions,
}: Props) {
    const [gradeSheetOpen, setGradeSheetOpen] = useState(false);
    const [selectedSubmission, setSelectedSubmission] =
        useState<Submission | null>(null);

    if (!assignment) return null;

    const handleEdit = () => {
        onClose();
        if (onEdit) {
            onEdit(assignment);
        }
    };

    const handleGrade = (submission: Submission) => {
        setSelectedSubmission(submission);
        setGradeSheetOpen(true);
    };

    const handleGradeSuccess = () => {
        // Refresh submissions after grading
        if (onRefreshSubmissions) {
            onRefreshSubmissions();
        }
    };

    return (
        <Sheet open={open} onOpenChange={(open) => !open && onClose()}>
            <SheetContent className="overflow-y-auto p-0 sm:max-w-[800px]">
                <div className="p-6">
                    <SheetHeader className="pb-6 text-left">
                        <div className="flex items-start justify-between">
                            <div className="flex-1">
                                <SheetTitle className="text-2xl">
                                    {assignment.title}
                                </SheetTitle>
                                <SheetDescription className="mt-2">
                                    View assignment details and submissions
                                </SheetDescription>
                            </div>
                            <Badge
                                variant={
                                    assignment.is_published ? 'default' : 'secondary'
                                }
                            >
                                {assignment.is_published ? 'Published' : 'Draft'}
                            </Badge>
                        </div>
                    </SheetHeader>

                    <Tabs defaultValue="general" className="space-y-6">
                        <TabsList className="grid w-full grid-cols-2">
                            <TabsTrigger value="general">General</TabsTrigger>
                            <TabsTrigger value="submissions">
                                Submissions ({submissions?.length || 0})
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="general" className="space-y-6">
                        {/* Basic Information */}
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold">Basic Information</h3>

                            <div>
                                <Label className="text-muted-foreground">
                                    Assignment Type
                                </Label>
                                <div className="mt-1">
                                    <Badge variant="outline">
                                        {assignmentTypeLabels[assignment.assignment_type]}
                                    </Badge>
                                </div>
                            </div>

                            {assignment.description && (
                                <div>
                                    <Label className="text-muted-foreground">
                                        Description
                                    </Label>
                                    <p className="mt-1 text-sm">
                                        {assignment.description}
                                    </p>
                                </div>
                            )}

                            {assignment.instructions && (
                                <div>
                                    <Label className="text-muted-foreground">
                                        Instructions
                                    </Label>
                                    <p className="mt-1 whitespace-pre-wrap text-sm">
                                        {assignment.instructions}
                                    </p>
                                </div>
                            )}

                            {assignment.target_year_levels &&
                                assignment.target_year_levels.length > 0 && (
                                    <div>
                                        <Label className="text-muted-foreground">
                                            Target Year Levels
                                        </Label>
                                        <div className="mt-2 flex flex-wrap gap-2">
                                            {assignment.target_year_levels.map((level) => (
                                                <Badge key={level} variant="secondary">
                                                    {level}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                )}
                        </div>

                        {/* Submission Details */}
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold">
                                Submission Details
                            </h3>

                            <div className="grid gap-4 sm:grid-cols-2">
                                {assignment.due_date && (
                                    <div>
                                        <Label className="text-muted-foreground">
                                            Due Date
                                        </Label>
                                        <div className="mt-1 flex items-center gap-2">
                                            <Calendar className="size-4" />
                                            <span className="text-sm">
                                                {new Date(
                                                    assignment.due_date,
                                                ).toLocaleString()}
                                            </span>
                                        </div>
                                    </div>
                                )}

                                <div>
                                    <Label className="text-muted-foreground">
                                        Maximum Score
                                    </Label>
                                    <p className="mt-1 text-sm">
                                        {assignment.max_score} points
                                    </p>
                                </div>

                                <div>
                                    <Label className="text-muted-foreground">
                                        Late Submission
                                    </Label>
                                    <p className="mt-1 text-sm">
                                        {assignment.allow_late_submission
                                            ? `Allowed (${assignment.late_penalty_percent}% penalty)`
                                            : 'Not Allowed'}
                                    </p>
                                </div>

                                {assignment.allow_late_submission &&
                                    assignment.late_submission_until && (
                                        <div>
                                            <Label className="text-muted-foreground">
                                                Late Submission Until
                                            </Label>
                                            <p className="mt-1 text-sm">
                                                {new Date(
                                                    assignment.late_submission_until,
                                                ).toLocaleString()}
                                            </p>
                                        </div>
                                    )}

                                <div>
                                    <Label className="text-muted-foreground">
                                        Resubmission
                                    </Label>
                                    <p className="mt-1 text-sm">
                                        {assignment.allow_resubmission
                                            ? `Allowed (max ${assignment.max_submissions} submissions)`
                                            : 'Not Allowed'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* File Upload Requirements */}
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold">
                                File Upload Requirements
                            </h3>

                            <div>
                                <Label className="text-muted-foreground">
                                    Allowed File Types
                                </Label>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {assignment.allowed_file_types?.map((type) => (
                                        <Badge key={type} variant="outline">
                                            {fileTypeLabels[type] || type.toUpperCase()}
                                        </Badge>
                                    ))}
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label className="text-muted-foreground">
                                        Maximum File Size
                                    </Label>
                                    <p className="mt-1 text-sm">
                                        {assignment.max_file_size_mb} MB per file
                                    </p>
                                </div>

                                <div>
                                    <Label className="text-muted-foreground">
                                        Maximum Files
                                    </Label>
                                    <p className="mt-1 text-sm">
                                        {assignment.max_files} file(s)
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Submission Statistics */}
                        {(assignment.submissions_count !== undefined ||
                            assignment.graded_count !== undefined) && (
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold">
                                    Submission Statistics
                                </h3>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div className="rounded-lg border p-4">
                                        <div className="flex items-center gap-2">
                                            <Users className="size-4 text-muted-foreground" />
                                            <Label className="text-muted-foreground">
                                                Submissions
                                            </Label>
                                        </div>
                                        <p className="mt-2 text-2xl font-bold">
                                            {assignment.submissions_count || 0}
                                        </p>
                                    </div>

                                    <div className="rounded-lg border p-4">
                                        <div className="flex items-center gap-2">
                                            <FileText className="size-4 text-muted-foreground" />
                                            <Label className="text-muted-foreground">
                                                Graded
                                            </Label>
                                        </div>
                                        <p className="mt-2 text-2xl font-bold">
                                            {assignment.graded_count || 0}
                                        </p>
                                    </div>

                                    <div className="rounded-lg border p-4">
                                        <div className="flex items-center gap-2">
                                            <FileText className="size-4 text-muted-foreground" />
                                            <Label className="text-muted-foreground">
                                                Pending
                                            </Label>
                                        </div>
                                        <p className="mt-2 text-2xl font-bold">
                                            {(assignment.submissions_count || 0) -
                                                (assignment.graded_count || 0)}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}
                        </TabsContent>

                        <TabsContent value="submissions" className="space-y-4">
                            {submissions && submissions.length > 0 ? (
                                <div className="space-y-4">
                                    {submissions.map((submission) => (
                                        <div
                                            key={submission.id}
                                            className="rounded-lg border"
                                        >
                                            {/* Submission Header */}
                                            <div className="border-b bg-muted/30 p-4">
                                                <div className="flex items-start justify-between gap-4">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2">
                                                            <h4 className="font-semibold">
                                                                {submission.resident_name}
                                                            </h4>
                                                            <Badge variant="outline">
                                                                {submission.year_level}
                                                            </Badge>
                                                            <Badge
                                                                variant={
                                                                    statusLabels[
                                                                        submission.status
                                                                    ]?.variant ||
                                                                    'secondary'
                                                                }
                                                            >
                                                                {statusLabels[
                                                                    submission.status
                                                                ]?.label ||
                                                                    submission.status}
                                                            </Badge>
                                                            {submission.is_late && (
                                                                <Badge variant="destructive">
                                                                    Late
                                                                    {submission.late_days >
                                                                        0 &&
                                                                        ` (${submission.late_days}d)`}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <div className="mt-2 flex items-center gap-4 text-sm text-muted-foreground">
                                                            <div className="flex items-center gap-1">
                                                                <Calendar className="size-3" />
                                                                Submitted:{' '}
                                                                {new Date(
                                                                    submission.submitted_at,
                                                                ).toLocaleString()}
                                                            </div>
                                                            {submission.score !== null && (
                                                                <div className="font-medium text-foreground">
                                                                    Score:{' '}
                                                                    {submission.score} /{' '}
                                                                    {submission.max_score} (
                                                                    {submission.percentage?.toFixed(
                                                                        0,
                                                                    )}
                                                                    %)
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            handleGrade(submission)
                                                        }
                                                    >
                                                        {submission.status === 'graded'
                                                            ? 'Review'
                                                            : 'Grade'}
                                                    </Button>
                                                </div>
                                            </div>

                                            {/* Submission Notes/Comments */}
                                            {submission.submission_text && (
                                                <div className="border-b p-4">
                                                    <Label className="mb-2 text-sm text-muted-foreground">
                                                        Notes / Comments
                                                    </Label>
                                                    <div className="mt-2 rounded-lg bg-muted/50 p-3">
                                                        <p className="whitespace-pre-wrap text-sm">
                                                            {submission.submission_text}
                                                        </p>
                                                    </div>
                                                </div>
                                            )}

                                            {/* Attached Files */}
                                            {submission.files &&
                                                submission.files.length > 0 && (
                                                    <div className="p-4">
                                                        <Label className="mb-2 text-sm text-muted-foreground">
                                                            Attached Files (
                                                            {submission.files.length})
                                                        </Label>
                                                        <div className="mt-2 space-y-2">
                                                            {submission.files.map(
                                                                (file) => (
                                                                    <div
                                                                        key={file.id}
                                                                        className="flex items-center justify-between rounded border p-2"
                                                                    >
                                                                        <div className="flex items-center gap-2">
                                                                            <FileText className="size-4 text-muted-foreground" />
                                                                            <div>
                                                                                <p className="text-sm font-medium">
                                                                                    {
                                                                                        file.original_name
                                                                                    }
                                                                                </p>
                                                                                <p className="text-xs text-muted-foreground">
                                                                                    {(
                                                                                        file.file_size /
                                                                                        (1024 *
                                                                                            1024)
                                                                                    ).toFixed(
                                                                                        2,
                                                                                    )}{' '}
                                                                                    MB
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                        <Button
                                                                            size="sm"
                                                                            variant="ghost"
                                                                            asChild
                                                                        >
                                                                            <a
                                                                                href={`/storage/${file.file_path}`}
                                                                                download={
                                                                                    file.original_name
                                                                                }
                                                                                target="_blank"
                                                                                rel="noopener noreferrer"
                                                                            >
                                                                                <Download className="size-4" />
                                                                            </a>
                                                                        </Button>
                                                                    </div>
                                                                ),
                                                            )}
                                                        </div>
                                                    </div>
                                                )}
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center py-12 text-center">
                                    <FileText className="mb-4 size-12 text-muted-foreground" />
                                    <h3 className="mb-2 text-lg font-semibold">
                                        No submissions yet
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        Submissions from residents will appear here
                                    </p>
                                </div>
                            )}
                        </TabsContent>
                    </Tabs>

                    <div className="mt-6 flex justify-end gap-2 border-t pt-6">
                        <Button variant="outline" onClick={onClose}>
                            Close
                        </Button>
                        {onEdit && (
                            <Button onClick={handleEdit}>Edit Assignment</Button>
                        )}
                    </div>
                </div>
            </SheetContent>

            {/* Grade Submission Sheet (nested sheet) */}
            <GradeSubmissionSheet
                open={gradeSheetOpen}
                submission={selectedSubmission}
                onClose={() => setGradeSheetOpen(false)}
                onSuccess={handleGradeSuccess}
            />
        </Sheet>
    );
}

