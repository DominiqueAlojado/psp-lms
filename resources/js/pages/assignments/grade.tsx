import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, router, useForm } from '@inertiajs/react';
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

            <div className="space-y-6 p-6">
                <div className="space-y-4">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.visit('/assignments')}
                    >
                        <ArrowLeft className="mr-2 size-4" />
                        Back
                    </Button>
                    <HeadingSmall
                        title="Grade Submission"
                        description="Review resident work, download evidence, and save structured grading feedback."
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <StatCard
                        title="Maximum Score"
                        value={submission.max_score}
                        description="Total points available for this submission"
                        icon={FileText}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Current Score"
                        value={submission.score ?? 'Not graded'}
                        description="Most recent saved grading value"
                        icon={Calendar}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Submission Status"
                        value={submission.status === 'graded' ? 'Graded' : 'Awaiting Grade'}
                        description={submission.is_late ? `Late by ${submission.late_days} day(s)` : 'Submitted within the allowed window'}
                        icon={User}
                        iconColor="text-primary"
                    />
                </div>

                <Card className="overflow-hidden border-primary/10 bg-[linear-gradient(135deg,rgba(248,244,255,0.98),rgba(255,255,255,0.94))]">
                    <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Grading context
                            </p>
                            <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                {submission.assignment_title}
                            </h3>
                            <p className="text-sm leading-6 text-muted-foreground">
                                Validate the submission details, review uploaded files, and leave actionable feedback for the resident.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="outline">
                                {assignmentTypeLabels[submission.assignment_type]}
                            </Badge>
                            <Badge variant={submission.status === 'graded' ? 'default' : 'secondary'}>
                                {submission.status === 'graded' ? 'Graded' : 'Awaiting Grade'}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                    <CardHeader className="pb-3">
                        <CardTitle>Submission Information</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                    Assignment
                                </Label>
                                <p className="mt-1 text-sm font-medium">
                                    {submission.assignment_title}
                                </p>
                                <Badge variant="outline" className="mt-1">
                                    {assignmentTypeLabels[submission.assignment_type]}
                                </Badge>
                            </div>

                            <div>
                                <Label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                    Resident
                                </Label>
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
                                <Label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                    Submitted
                                </Label>
                                <div className="mt-1 flex items-center gap-2">
                                    <Calendar className="size-4" />
                                    <span className="text-sm">
                                        {new Date(
                                            submission.submitted_at,
                                        ).toLocaleString()}
                                    </span>
                                    {submission.is_late ? (
                                        <Badge variant="destructive">
                                            Late ({submission.late_days}d)
                                        </Badge>
                                    ) : null}
                                </div>
                            </div>

                            <div>
                                <Label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                    Status
                                </Label>
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

                        {submission.submission_text ? (
                            <div>
                                <Label className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                    Resident Notes
                                </Label>
                                <p className="mt-1 whitespace-pre-wrap text-sm">
                                    {submission.submission_text}
                                </p>
                            </div>
                        ) : null}
                    </CardContent>
                </Card>

                <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                    <CardHeader className="pb-3">
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
                                        className="flex items-center justify-between rounded-2xl border border-border/70 bg-background/80 p-4"
                                    >
                                        <div className="flex items-center gap-3">
                                            <FileText className="size-5 text-muted-foreground" />
                                            <div>
                                                <p className="font-medium">
                                                    {file.original_name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {file.file_type.toUpperCase()} -{' '}
                                                    {file.file_size_formatted} - Downloaded{' '}
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

                <form onSubmit={handleSubmit}>
                    <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                        <CardHeader className="pb-3">
                            <CardTitle>Grade & Feedback</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label
                                    htmlFor="score"
                                    className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                                >
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
                                {errors.score ? (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.score}
                                    </p>
                                ) : null}
                            </div>

                            <div>
                                <Label
                                    htmlFor="grader_feedback"
                                    className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                                >
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
                                {errors.grader_feedback ? (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.grader_feedback}
                                    </p>
                                ) : null}
                            </div>

                            <div className="flex justify-end gap-2 border-t border-border/70 pt-5">
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
