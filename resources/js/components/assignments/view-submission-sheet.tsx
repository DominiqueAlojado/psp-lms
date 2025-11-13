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
import { Calendar, Clock, Download, FileText } from 'lucide-react';

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
    is_overdue: boolean;
    submission: SubmissionInfo | null;
}

interface Props {
    open: boolean;
    assignment: Assignment | null;
    onClose: () => void;
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

export function ViewSubmissionSheet({ open, assignment, onClose }: Props) {
    if (!assignment || !assignment.submission) return null;

    const submission = assignment.submission;

    const formatFileSize = (bytes: number) => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(2)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    };

    return (
        <Sheet open={open} onOpenChange={(open) => !open && onClose()}>
            <SheetContent className="overflow-y-auto p-0 sm:max-w-[700px]">
                <div className="p-6">
                    <SheetHeader className="pb-6 text-left">
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex-1">
                                <SheetTitle className="text-2xl">
                                    {assignment.title}
                                </SheetTitle>
                                <SheetDescription className="mt-2 flex items-center gap-2">
                                    <Badge variant="outline">
                                        {
                                            assignmentTypeLabels[
                                                assignment.assignment_type
                                            ]
                                        }
                                    </Badge>
                                </SheetDescription>
                            </div>
                            <Badge
                                variant={
                                    statusLabels[submission.status]?.variant ||
                                    'secondary'
                                }
                            >
                                {statusLabels[submission.status]?.label ||
                                    submission.status}
                                {submission.is_late && ' (Late)'}
                            </Badge>
                        </div>
                    </SheetHeader>

                    <div className="space-y-6">
                        {/* Submission Info */}
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold">
                                Submission Information
                            </h3>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label className="text-muted-foreground">
                                        Submitted At
                                    </Label>
                                    <div className="mt-1 flex items-center gap-2">
                                        <Clock className="size-4" />
                                        <span className="text-sm">
                                            {new Date(
                                                submission.submitted_at,
                                            ).toLocaleString()}
                                        </span>
                                    </div>
                                </div>

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

                                {submission.score !== null && (
                                    <div>
                                        <Label className="text-muted-foreground">
                                            Score
                                        </Label>
                                        <p className="mt-1 text-lg font-bold">
                                            {submission.score} / {assignment.max_score}
                                        </p>
                                    </div>
                                )}

                                {submission.is_late && (
                                    <div>
                                        <Label className="text-muted-foreground">
                                            Status
                                        </Label>
                                        <div className="mt-1">
                                            <Badge variant="destructive">
                                                Late Submission
                                            </Badge>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Submitted Files */}
                        {submission.files && submission.files.length > 0 && (
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold">
                                    Attached Files ({submission.files.length})
                                </h3>

                                <div className="space-y-2">
                                    {submission.files.map((file) => (
                                        <div
                                            key={file.id}
                                            className="flex items-center justify-between rounded-lg border p-3"
                                        >
                                            <div className="flex items-center gap-3">
                                                <FileText className="size-5 text-muted-foreground" />
                                                <div>
                                                    <p className="text-sm font-medium">
                                                        {file.original_name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatFileSize(file.file_size)} •{' '}
                                                        {file.mime_type}
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
                                                    download={file.original_name}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <Download className="size-4" />
                                                </a>
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Submission Notes */}
                        {submission.submission_text && (
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold">
                                    Notes / Comments
                                </h3>
                                <div className="rounded-lg border p-4">
                                    <p className="whitespace-pre-wrap text-sm">
                                        {submission.submission_text}
                                    </p>
                                </div>
                            </div>
                        )}

                        {/* Grader Feedback */}
                        {submission.grader_feedback && (
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold">
                                    Feedback from Grader
                                </h3>
                                <div className="rounded-lg border border-primary/20 bg-primary/5 p-4">
                                    <p className="whitespace-pre-wrap text-sm">
                                        {submission.grader_feedback}
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="mt-6 flex justify-end gap-2 border-t pt-6">
                        <Button variant="outline" onClick={onClose}>
                            Close
                        </Button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}

