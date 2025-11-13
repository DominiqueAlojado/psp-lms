import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import { router } from '@inertiajs/react';
import { AlertCircle, Calendar, FileText, Upload, X } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

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

export function SubmitAssignmentSheet({ open, assignment, onClose }: Props) {
    const [submissionText, setSubmissionText] = useState('');
    const [files, setFiles] = useState<File[]>([]);
    const [fileErrors, setFileErrors] = useState<string[]>([]);
    const [processing, setProcessing] = useState(false);

    const handleClose = () => {
        setSubmissionText('');
        setFiles([]);
        setFileErrors([]);
        onClose();
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (!assignment) return;

        const selectedFiles = Array.from(e.target.files || []);
        const newErrors: string[] = [];

        // Validate file count
        if (files.length + selectedFiles.length > assignment.max_files) {
            newErrors.push(`Maximum ${assignment.max_files} files allowed`);
        }

        // Validate file types and sizes
        selectedFiles.forEach((file) => {
            const ext = file.name.split('.').pop()?.toLowerCase() || '';
            if (
                assignment.allowed_file_types &&
                !assignment.allowed_file_types.includes(ext)
            ) {
                newErrors.push(
                    `${file.name}: Invalid file type. Allowed: ${assignment.allowed_file_types.join(', ')}`,
                );
            }

            const fileSizeMB = file.size / (1024 * 1024);
            if (fileSizeMB > assignment.max_file_size_mb) {
                newErrors.push(
                    `${file.name}: File too large (max ${assignment.max_file_size_mb}MB)`,
                );
            }
        });

        if (newErrors.length > 0) {
            setFileErrors(newErrors);
            e.target.value = '';
            return;
        }

        setFileErrors([]);
        setFiles([...files, ...selectedFiles]);
        e.target.value = '';
    };

    const removeFile = (index: number) => {
        setFiles(files.filter((_, i) => i !== index));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!assignment) return;

        if (files.length === 0) {
            setFileErrors(['Please upload at least one file']);
            toast.error('Please upload at least one file');
            return;
        }

        setProcessing(true);

        const formData = new FormData();
        formData.append('submission_text', submissionText);
        files.forEach((file) => {
            formData.append('files[]', file);
        });

        router.post(`/assignments/${assignment.id}/submit`, formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                toast.success('Assignment submitted successfully!');
                handleClose();
            },
            onError: (errors) => {
                toast.error('Failed to submit assignment. Please check the form.');
            },
            onFinish: () => {
                setProcessing(false);
            },
        });
    };

    if (!assignment) return null;

    return (
        <Sheet open={open} onOpenChange={(open) => !open && handleClose()}>
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
                                        {assignmentTypeLabels[assignment.assignment_type]}
                                    </Badge>
                                    {assignment.due_date && (
                                        <>
                                            <span className="text-muted-foreground">•</span>
                                            <div className="flex items-center gap-1 text-sm">
                                                <Calendar className="size-3" />
                                                Due:{' '}
                                                {new Date(
                                                    assignment.due_date,
                                                ).toLocaleString()}
                                            </div>
                                        </>
                                    )}
                                </SheetDescription>
                            </div>
                            {assignment.is_overdue && (
                                <Badge variant="destructive">Overdue</Badge>
                            )}
                        </div>
                    </SheetHeader>

                    {assignment.can_still_submit ? (
                        <>
                            {/* Assignment Details */}
                            <div className="mb-6 space-y-4 rounded-lg border p-4">
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

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <Label className="text-xs text-muted-foreground">
                                            Max Score
                                        </Label>
                                        <p className="mt-1 text-sm font-medium">
                                            {assignment.max_score} points
                                        </p>
                                    </div>
                                    <div>
                                        <Label className="text-xs text-muted-foreground">
                                            Allowed Types
                                        </Label>
                                        <p className="mt-1 text-sm">
                                            {assignment.allowed_file_types?.join(', ').toUpperCase() || 'Any'}
                                        </p>
                                    </div>
                                    <div>
                                        <Label className="text-xs text-muted-foreground">
                                            Max Size
                                        </Label>
                                        <p className="mt-1 text-sm">
                                            {assignment.max_file_size_mb} MB per file
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Submission Form */}
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div>
                                    <Label htmlFor="submission_text">
                                        Notes / Comments (Optional)
                                    </Label>
                                    <Textarea
                                        id="submission_text"
                                        value={submissionText}
                                        onChange={(e) =>
                                            setSubmissionText(e.target.value)
                                        }
                                        placeholder="Add any notes or comments about your submission"
                                        rows={4}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="files">
                                        Upload Files{' '}
                                        <span className="text-destructive">*</span>
                                    </Label>
                                    <div className="mt-2">
                                        <Input
                                            id="files"
                                            type="file"
                                            multiple
                                            onChange={handleFileChange}
                                            accept={
                                                assignment.allowed_file_types
                                                    ?.map((t) => `.${t}`)
                                                    .join(',') || '*'
                                            }
                                        />
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Max {assignment.max_files} files, up to{' '}
                                            {assignment.max_file_size_mb}MB each
                                        </p>
                                    </div>

                                    {fileErrors.length > 0 && (
                                        <div className="mt-2 space-y-1">
                                            {fileErrors.map((error, index) => (
                                                <p
                                                    key={index}
                                                    className="text-sm text-destructive"
                                                >
                                                    {error}
                                                </p>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                {/* Selected Files List */}
                                {files.length > 0 && (
                                    <div>
                                        <Label>
                                            Selected Files ({files.length})
                                        </Label>
                                        <div className="mt-2 space-y-2">
                                            {files.map((file, index) => (
                                                <div
                                                    key={index}
                                                    className="flex items-center justify-between rounded-lg border p-2"
                                                >
                                                    <div className="flex items-center gap-2">
                                                        <FileText className="size-4 text-muted-foreground" />
                                                        <span className="text-sm">
                                                            {file.name}
                                                        </span>
                                                        <span className="text-xs text-muted-foreground">
                                                            (
                                                            {(
                                                                file.size /
                                                                (1024 * 1024)
                                                            ).toFixed(2)}{' '}
                                                            MB)
                                                        </span>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            removeFile(index)
                                                        }
                                                    >
                                                        <X className="size-4" />
                                                    </Button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Submit Actions */}
                                <div className="flex justify-end gap-2 border-t pt-6">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={handleClose}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={processing || files.length === 0}
                                    >
                                        <Upload className="mr-2 size-4" />
                                        {processing
                                            ? 'Submitting...'
                                            : 'Submit Assignment'}
                                    </Button>
                                </div>
                            </form>
                        </>
                    ) : (
                        <div className="flex flex-col items-center justify-center py-12">
                            <AlertCircle className="mb-4 size-12 text-destructive" />
                            <h3 className="mb-2 text-lg font-semibold">
                                Submission Closed
                            </h3>
                            <p className="text-center text-sm text-muted-foreground">
                                This assignment is no longer accepting submissions
                            </p>
                            <Button
                                className="mt-4"
                                variant="outline"
                                onClick={handleClose}
                            >
                                Close
                            </Button>
                        </div>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}

