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
import { Calendar, FileText, Users } from 'lucide-react';

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
    open: boolean;
    assignment: Assignment | null;
    onClose: () => void;
    onEdit?: (assignment: Assignment) => void;
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

export function ViewAssignmentSheet({ open, assignment, onClose, onEdit }: Props) {
    if (!assignment) return null;

    const handleEdit = () => {
        onClose();
        if (onEdit) {
            onEdit(assignment);
        }
    };

    return (
        <Sheet open={open} onOpenChange={(open) => !open && onClose()}>
            <SheetContent className="overflow-y-auto p-0 sm:max-w-[700px]">
                <div className="p-6">
                    <SheetHeader className="pb-6 text-left">
                        <div className="flex items-start justify-between">
                            <div className="flex-1">
                                <SheetTitle className="text-2xl">
                                    {assignment.title}
                                </SheetTitle>
                                <SheetDescription className="mt-2">
                                    View assignment details and submission information
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

                    <div className="space-y-6">
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
                    </div>

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
        </Sheet>
    );
}

