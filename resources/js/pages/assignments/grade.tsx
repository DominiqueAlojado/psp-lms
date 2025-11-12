import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    Download,
    FileText,
    User,
} from 'lucide-react';

interface SubmissionFile {
    id: number;
    original_name: string;
    file_type: string;
    file_size_formatted: string;
    download_count: number;
}

interface Submission {
    id: number;
    assignment_title: string;
    assignment_type: string;
    resident_name: string;
    year_level: string;
    submission_text: string | null;
    submitted_at: string;
    is_late: boolean;
    late_days: number;
    max_score: number;
    score: number | null;
    status: string;
    grader_feedback: string | null;
    files: SubmissionFile[];
}

interface Props {
    submission: Submission;
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

export default function GradeSubmission({ submission }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        score: submission.score ?? 0,
        grader_feedback: submission.grader_feedback ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/submissions/${submission.id}/grade`, {
            onSuccess: () => {
                router.visit(`/assignments/${submission.id}`);
            },
        });
    };

    const handleDownload = (fileId: number) => {
        window.open(`/submission-files/${fileId}/download`, '_blank');
    };

    return (
        <AppLayout>
            <Head title={`Grade: ${submission.resident_name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <Button variant="ghost" size="sm" onClick={() => router.visit('/assignments')}>
                        <ArrowLeft className="mr-2 size-4" />
                        Back
                    </Button>
                    <h1 className="mt-2 text-3xl font-bold">
                        Grade Submission
                    </h1>
                    <p className="text-muted-foreground">
                        Review and grade resident submission
                    </p>
                </div>

                {/* Submission Info */}
                <Card>
                    <CardHeader>
                        <CardTitle>Submission Information</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Assignment</Label>
                                <p className="mt-1 text-sm font-medium">
                                    {submission.assignment_title}
                                </p>
                                <Badge variant="outline" className="mt-1">
                                    {assignmentTypeLabels[submission.assignment_type]}
                                </Badge>
                            </div>

                            <div>
                                <Label>Resident</Label>
                                <div className="mt-1 flex items-center gap-2">
                                    <User className="size-4" />
                                    <span className="text-sm font-medium">
                                        {submission.resident_name}
                                    </span>
                                    <Badge variant="outline">
                                        {submission.year_level}
                                    </Badge>
                                </div>
                            </div>

                            <div>
                                <Label>Submitted</Label>
                                <div className="mt-1 flex items-center gap-2">
                                    <Calendar className="size-4" />
                                    <span className="text-sm">
                                        {new Date(
                                            submission.submitted_at,
                                        ).toLocaleString()}
                                    </span>
                                    {submission.is_late && (
                                        <Badge variant="destructive">
                                            Late ({submission.late_days}d)
                                        </Badge>
                                    )}
                                </div>
                            </div>

                            <div>
                                <Label>Status</Label>
                                <p className="mt-1">
                                    <Badge
                                        variant={
                                            submission.status === 'graded'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {submission.status === 'graded'
                                            ? 'Graded'
                                            : 'Awaiting Grade'}
                                    </Badge>
                                </p>
                            </div>
                        </div>

                        {submission.submission_text && (
                            <div>
                                <Label>Resident Notes</Label>
                                <p className="mt-1 whitespace-pre-wrap text-sm">
                                    {submission.submission_text}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Submitted Files */}
                <Card>
                    <CardHeader>
                        <CardTitle>
                            Submitted Files ({submission.files.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {submission.files.length > 0 ? (
                            <div className="space-y-2">
                                {submission.files.map((file) => (
                                    <div
                                        key={file.id}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div className="flex items-center gap-3">
                                            <FileText className="size-5 text-muted-foreground" />
                                            <div>
                                                <p className="font-medium">
                                                    {file.original_name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {file.file_type.toUpperCase()} •{' '}
                                                    {file.file_size_formatted} • Downloaded{' '}
                                                    {file.download_count} times
                                                </p>
                                            </div>
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => handleDownload(file.id)}
                                        >
                                            <Download className="mr-2 size-4" />
                                            Download
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-center text-sm text-muted-foreground">
                                No files uploaded
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Grading Form */}
                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Grade & Feedback</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="score">
                                    Score (out of {submission.max_score}){' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="score"
                                    type="number"
                                    value={data.score}
                                    onChange={(e) =>
                                        setData('score', parseFloat(e.target.value))
                                    }
                                    min={0}
                                    max={submission.max_score}
                                    step={0.5}
                                    className="max-w-[200px]"
                                />
                                {errors.score && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.score}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="grader_feedback">
                                    Feedback for Resident
                                </Label>
                                <Textarea
                                    id="grader_feedback"
                                    value={data.grader_feedback}
                                    onChange={(e) =>
                                        setData('grader_feedback', e.target.value)
                                    }
                                    placeholder="Provide constructive feedback for the resident..."
                                    rows={6}
                                />
                                {errors.grader_feedback && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.grader_feedback}
                                    </p>
                                )}
                            </div>

                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.visit('/assignments')}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Saving...' : 'Save Grade'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}

