import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm, Link } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Calendar,
    FileText,
    Upload,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';

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
    assignment: Assignment;
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

export default function SubmitAssignment({ assignment }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        submission_text: '',
        files: [] as File[],
    });

    const [fileErrors, setFileErrors] = useState<string[]>([]);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const selectedFiles = Array.from(e.target.files || []);
        const newErrors: string[] = [];

        // Validate file count
        if (
            data.files.length + selectedFiles.length >
            assignment.max_files
        ) {
            newErrors.push(
                `Maximum ${assignment.max_files} files allowed`,
            );
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
            return;
        }

        setFileErrors([]);
        setData('files', [...data.files, ...selectedFiles]);
    };

    const removeFile = (index: number) => {
        setData(
            'files',
            data.files.filter((_, i) => i !== index),
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (data.files.length === 0) {
            setFileErrors(['Please upload at least one file']);
            return;
        }

        post(`/assignments/${assignment.id}/submit`);
    };

    return (
        <AppLayout>
            <Head title={`Submit: ${assignment.title}`} />

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/my-assignments">
                            <ArrowLeft className="mr-2 size-4" />
                            Back
                        </Link>
                    </Button>
                    <h1 className="mt-2 text-3xl font-bold">{assignment.title}</h1>
                    <div className="mt-2 flex items-center gap-2">
                        <Badge variant="outline">
                            {assignmentTypeLabels[assignment.assignment_type]}
                        </Badge>
                        {assignment.due_date && (
                            <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                <Calendar className="size-3" />
                                Due:{' '}
                                {new Date(assignment.due_date).toLocaleString()}
                            </div>
                        )}
                        {assignment.is_overdue && (
                            <Badge variant="destructive">Overdue</Badge>
                        )}
                    </div>
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
                                <p className="mt-1 text-sm">
                                    {assignment.description}
                                </p>
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

                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <Label>Max Score</Label>
                                <p className="mt-1 text-sm font-medium">
                                    {assignment.max_score} points
                                </p>
                            </div>
                            <div>
                                <Label>Allowed File Types</Label>
                                <p className="mt-1 text-sm">
                                    {assignment.allowed_file_types.join(', ').toUpperCase()}
                                </p>
                            </div>
                            <div>
                                <Label>Max File Size</Label>
                                <p className="mt-1 text-sm">
                                    {assignment.max_file_size_mb} MB per file
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Submission Form */}
                {assignment.can_still_submit ? (
                    <form onSubmit={handleSubmit}>
                        <div className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Your Submission</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <Label htmlFor="submission_text">
                                            Notes / Comments (Optional)
                                        </Label>
                                        <Textarea
                                            id="submission_text"
                                            value={data.submission_text}
                                            onChange={(e) =>
                                                setData(
                                                    'submission_text',
                                                    e.target.value,
                                                )
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
                                                accept={assignment.allowed_file_types
                                                    .map((t) => `.${t}`)
                                                    .join(',')}
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

                                        {errors.files && (
                                            <p className="mt-1 text-sm text-destructive">
                                                {errors.files}
                                            </p>
                                        )}
                                    </div>

                                    {/* Selected Files List */}
                                    {data.files.length > 0 && (
                                        <div>
                                            <Label>Selected Files ({data.files.length})</Label>
                                            <div className="mt-2 space-y-2">
                                                {data.files.map((file, index) => (
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
                                                            onClick={() => removeFile(index)}
                                                        >
                                                            <X className="size-4" />
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Submit Button */}
                            <div className="flex justify-end gap-2">
                                <Button variant="outline" asChild>
                                    <Link href="/my-assignments">Cancel</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing || data.files.length === 0}
                                >
                                    <Upload className="mr-2 size-4" />
                                    {processing ? 'Submitting...' : 'Submit Assignment'}
                                </Button>
                            </div>
                        </div>
                    </form>
                ) : (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <AlertCircle className="mb-4 size-12 text-destructive" />
                            <h3 className="mb-2 text-lg font-semibold">
                                Submission Closed
                            </h3>
                            <p className="text-center text-sm text-muted-foreground">
                                This assignment is no longer accepting submissions
                            </p>
                            <Button className="mt-4" asChild>
                                <Link href="/my-assignments">Back to Assignments</Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

