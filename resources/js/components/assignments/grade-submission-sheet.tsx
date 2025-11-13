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
import { Calendar, Download, FileText, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

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
    submission_text?: string | null;
    is_late: boolean;
    late_days: number;
    files?: SubmissionFile[];
}

interface Props {
    open: boolean;
    submission: Submission | null;
    onClose: () => void;
    onSuccess?: () => void;
}

export function GradeSubmissionSheet({
    open,
    submission,
    onClose,
    onSuccess,
}: Props) {
    const [score, setScore] = useState(submission?.score ?? 0);
    const [feedback, setFeedback] = useState('');
    const [processing, setProcessing] = useState(false);

    const handleClose = () => {
        setScore(0);
        setFeedback('');
        onClose();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!submission) return;

        setProcessing(true);

        router.post(
            `/submissions/${submission.id}/grade`,
            {
                score,
                grader_feedback: feedback,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Submission graded successfully!');
                    handleClose();
                    if (onSuccess) {
                        onSuccess();
                    }
                },
                onError: () => {
                    toast.error('Failed to grade submission');
                },
                onFinish: () => {
                    setProcessing(false);
                },
            },
        );
    };

    const formatFileSize = (bytes: number) => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(2)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    };

    if (!submission) return null;

    return (
        <Sheet open={open} onOpenChange={(open) => !open && handleClose()}>
            <SheetContent className="overflow-y-auto p-0 sm:max-w-[700px]">
                <div className="p-6">
                    <SheetHeader className="pb-6 text-left">
                        <SheetTitle className="text-2xl">
                            Grade Submission
                        </SheetTitle>
                        <SheetDescription>
                            Review and grade {submission.resident_name}'s submission
                        </SheetDescription>
                    </SheetHeader>

                    <div className="space-y-6">
                        {/* Submission Info */}
                        <div className="rounded-lg border p-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label className="text-xs text-muted-foreground">
                                        Resident
                                    </Label>
                                    <p className="mt-1 font-medium">
                                        {submission.resident_name}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-xs text-muted-foreground">
                                        Year Level
                                    </Label>
                                    <div className="mt-1">
                                        <Badge variant="outline">
                                            {submission.year_level}
                                        </Badge>
                                    </div>
                                </div>
                                <div>
                                    <Label className="text-xs text-muted-foreground">
                                        Submitted At
                                    </Label>
                                    <div className="mt-1 flex items-center gap-1 text-sm">
                                        <Calendar className="size-3" />
                                        {new Date(
                                            submission.submitted_at,
                                        ).toLocaleString()}
                                    </div>
                                </div>
                                <div>
                                    <Label className="text-xs text-muted-foreground">
                                        Status
                                    </Label>
                                    <div className="mt-1 flex items-center gap-2">
                                        {submission.is_late && (
                                            <Badge variant="destructive">
                                                Late
                                                {submission.late_days > 0 &&
                                                    ` (${submission.late_days}d)`}
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Submission Notes */}
                        {submission.submission_text && (
                            <div>
                                <Label className="text-muted-foreground">
                                    Notes / Comments from Resident
                                </Label>
                                <div className="mt-2 rounded-lg border bg-muted/50 p-3">
                                    <p className="whitespace-pre-wrap text-sm">
                                        {submission.submission_text}
                                    </p>
                                </div>
                            </div>
                        )}

                        {/* Attached Files */}
                        {submission.files && submission.files.length > 0 && (
                            <div>
                                <Label className="text-muted-foreground">
                                    Attached Files ({submission.files.length})
                                </Label>
                                <div className="mt-2 space-y-2">
                                    {submission.files.map((file) => (
                                        <div
                                            key={file.id}
                                            className="flex items-center justify-between rounded border p-3"
                                        >
                                            <div className="flex items-center gap-2">
                                                <FileText className="size-4 text-muted-foreground" />
                                                <div>
                                                    <p className="text-sm font-medium">
                                                        {file.original_name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatFileSize(file.file_size)}
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

                        {/* Grading Form */}
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="rounded-lg border p-4">
                                <h3 className="mb-4 text-lg font-semibold">
                                    Grading
                                </h3>

                                <div className="space-y-4">
                                    <div>
                                        <Label htmlFor="score">
                                            Score{' '}
                                            <span className="text-destructive">*</span>
                                        </Label>
                                        <div className="flex items-center gap-2">
                                            <Input
                                                id="score"
                                                type="number"
                                                value={score}
                                                onChange={(e) =>
                                                    setScore(parseInt(e.target.value))
                                                }
                                                min={0}
                                                max={submission.max_score}
                                                className="max-w-[150px]"
                                            />
                                            <span className="text-sm text-muted-foreground">
                                                / {submission.max_score}
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="feedback">
                                            Feedback / Comments
                                        </Label>
                                        <Textarea
                                            id="feedback"
                                            value={feedback}
                                            onChange={(e) => setFeedback(e.target.value)}
                                            placeholder="Provide feedback to the resident..."
                                            rows={6}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="flex justify-end gap-2 border-t pt-6">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleClose}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 size-4" />
                                    {processing ? 'Saving...' : 'Save Grade'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}

