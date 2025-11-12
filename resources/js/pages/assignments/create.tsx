import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

const ASSIGNMENT_TYPES = [
    { value: 'case_report', label: 'Case Report' },
    { value: 'procedure_log', label: 'Procedure Log' },
    { value: 'journal_review', label: 'Journal Review' },
    { value: 'presentation', label: 'Presentation' },
    { value: 'research_paper', label: 'Research Paper' },
    { value: 'reflection', label: 'Reflection' },
    { value: 'other', label: 'Other' },
];

const YEAR_LEVELS = ['PGY-1', 'PGY-2', 'PGY-3', 'PGY-4', 'PGY-5'];

const FILE_TYPES = [
    { value: 'pdf', label: 'PDF (.pdf)' },
    { value: 'doc', label: 'Word (.doc)' },
    { value: 'docx', label: 'Word (.docx)' },
    { value: 'ppt', label: 'PowerPoint (.ppt)' },
    { value: 'pptx', label: 'PowerPoint (.pptx)' },
    { value: 'jpg', label: 'Image (.jpg)' },
    { value: 'jpeg', label: 'Image (.jpeg)' },
    { value: 'png', label: 'Image (.png)' },
];

export default function CreateAssignment() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        instructions: '',
        assignment_type: 'case_report',
        target_year_levels: [] as string[],
        max_score: 100,
        due_date: '',
        allow_late_submission: false,
        late_submission_until: '',
        late_penalty_percent: 10,
        allow_resubmission: false,
        max_submissions: 1,
        allowed_file_types: ['pdf', 'docx', 'pptx'] as string[],
        max_file_size_mb: 10,
        max_files: 5,
        is_published: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/assignments', {
            onError: (errors) => {
                // If 419 CSRF error, reload page to get fresh token
                if (errors && typeof errors === 'object' && !Object.keys(errors).length) {
                    window.location.reload();
                }
            },
        });
    };

    const toggleYearLevel = (level: string) => {
        if (data.target_year_levels.includes(level)) {
            setData(
                'target_year_levels',
                data.target_year_levels.filter((l) => l !== level),
            );
        } else {
            setData('target_year_levels', [...data.target_year_levels, level]);
        }
    };

    const toggleFileType = (type: string) => {
        if (data.allowed_file_types.includes(type)) {
            setData(
                'allowed_file_types',
                data.allowed_file_types.filter((t) => t !== type),
            );
        } else {
            setData('allowed_file_types', [...data.allowed_file_types, type]);
        }
    };

    return (
        <AppLayout>
            <Head title="Create Assignment" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/assignments">
                                <ArrowLeft className="mr-2 size-4" />
                                Back
                            </Link>
                        </Button>
                        <h1 className="mt-2 text-3xl font-bold">
                            Create Assignment
                        </h1>
                        <p className="text-muted-foreground">
                            Create a new assignment for residents
                        </p>
                    </div>
                </div>

                {/* Assignment Form */}
                <form onSubmit={handleSubmit}>
                    <div className="space-y-6">
                        {/* Basic Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Basic Information</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label htmlFor="title">
                                        Title <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) =>
                                            setData('title', e.target.value)
                                        }
                                        placeholder="e.g., Case Report - Acute MI"
                                    />
                                    {errors.title && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.title}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="assignment_type">
                                        Assignment Type <span className="text-destructive">*</span>
                                    </Label>
                                    <Select
                                        value={data.assignment_type}
                                        onValueChange={(value) =>
                                            setData('assignment_type', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {ASSIGNMENT_TYPES.map((type) => (
                                                <SelectItem
                                                    key={type.value}
                                                    value={type.value}
                                                >
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.assignment_type && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.assignment_type}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) =>
                                            setData('description', e.target.value)
                                        }
                                        placeholder="Brief description of the assignment"
                                        rows={3}
                                    />
                                    {errors.description && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.description}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="instructions">Instructions</Label>
                                    <Textarea
                                        id="instructions"
                                        value={data.instructions}
                                        onChange={(e) =>
                                            setData('instructions', e.target.value)
                                        }
                                        placeholder="Detailed instructions for residents"
                                        rows={5}
                                    />
                                    {errors.instructions && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.instructions}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <Label>Target Year Levels (leave empty for all)</Label>
                                    <div className="mt-2 flex flex-wrap gap-3">
                                        {YEAR_LEVELS.map((level) => (
                                            <div
                                                key={level}
                                                className="flex items-center space-x-2"
                                            >
                                                <Checkbox
                                                    id={`year-${level}`}
                                                    checked={data.target_year_levels.includes(
                                                        level,
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggleYearLevel(level)
                                                    }
                                                />
                                                <Label
                                                    htmlFor={`year-${level}`}
                                                    className="font-normal"
                                                >
                                                    {level}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Submission Settings */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Submission Settings</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <Label htmlFor="due_date">Due Date</Label>
                                        <Input
                                            id="due_date"
                                            type="datetime-local"
                                            value={data.due_date}
                                            onChange={(e) =>
                                                setData('due_date', e.target.value)
                                            }
                                        />
                                        {errors.due_date && (
                                            <p className="mt-1 text-sm text-destructive">
                                                {errors.due_date}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <Label htmlFor="max_score">
                                            Max Score <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="max_score"
                                            type="number"
                                            value={data.max_score}
                                            onChange={(e) =>
                                                setData(
                                                    'max_score',
                                                    parseInt(e.target.value),
                                                )
                                            }
                                            min={1}
                                        />
                                        {errors.max_score && (
                                            <p className="mt-1 text-sm text-destructive">
                                                {errors.max_score}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="allow_late_submission"
                                        checked={data.allow_late_submission}
                                        onCheckedChange={(checked) =>
                                            setData('allow_late_submission', !!checked)
                                        }
                                    />
                                    <Label htmlFor="allow_late_submission">
                                        Allow late submission
                                    </Label>
                                </div>

                                {data.allow_late_submission && (
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <Label htmlFor="late_submission_until">
                                                Late Submission Until
                                            </Label>
                                            <Input
                                                id="late_submission_until"
                                                type="datetime-local"
                                                value={data.late_submission_until}
                                                onChange={(e) =>
                                                    setData(
                                                        'late_submission_until',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="late_penalty_percent">
                                                Late Penalty (%)
                                            </Label>
                                            <Input
                                                id="late_penalty_percent"
                                                type="number"
                                                value={data.late_penalty_percent}
                                                onChange={(e) =>
                                                    setData(
                                                        'late_penalty_percent',
                                                        parseInt(e.target.value),
                                                    )
                                                }
                                                min={0}
                                                max={100}
                                            />
                                        </div>
                                    </div>
                                )}

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="allow_resubmission"
                                        checked={data.allow_resubmission}
                                        onCheckedChange={(checked) =>
                                            setData('allow_resubmission', !!checked)
                                        }
                                    />
                                    <Label htmlFor="allow_resubmission">
                                        Allow resubmission
                                    </Label>
                                </div>

                                {data.allow_resubmission && (
                                    <div>
                                        <Label htmlFor="max_submissions">
                                            Maximum Submissions
                                        </Label>
                                        <Input
                                            id="max_submissions"
                                            type="number"
                                            value={data.max_submissions}
                                            onChange={(e) =>
                                                setData(
                                                    'max_submissions',
                                                    parseInt(e.target.value),
                                                )
                                            }
                                            min={1}
                                            max={10}
                                            className="max-w-[200px]"
                                        />
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* File Upload Settings */}
                        <Card>
                            <CardHeader>
                                <CardTitle>File Upload Settings</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label>Allowed File Types</Label>
                                    <div className="mt-2 grid grid-cols-2 gap-3 md:grid-cols-4">
                                        {FILE_TYPES.map((type) => (
                                            <div
                                                key={type.value}
                                                className="flex items-center space-x-2"
                                            >
                                                <Checkbox
                                                    id={`file-${type.value}`}
                                                    checked={data.allowed_file_types.includes(
                                                        type.value,
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggleFileType(type.value)
                                                    }
                                                />
                                                <Label
                                                    htmlFor={`file-${type.value}`}
                                                    className="font-normal"
                                                >
                                                    {type.label}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <Label htmlFor="max_file_size_mb">
                                            Max File Size (MB)
                                        </Label>
                                        <Input
                                            id="max_file_size_mb"
                                            type="number"
                                            value={data.max_file_size_mb}
                                            onChange={(e) =>
                                                setData(
                                                    'max_file_size_mb',
                                                    parseInt(e.target.value),
                                                )
                                            }
                                            min={1}
                                            max={100}
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="max_files">Max Files</Label>
                                        <Input
                                            id="max_files"
                                            type="number"
                                            value={data.max_files}
                                            onChange={(e) =>
                                                setData('max_files', parseInt(e.target.value))
                                            }
                                            min={1}
                                            max={20}
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Publishing */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Publishing</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_published"
                                        checked={data.is_published}
                                        onCheckedChange={(checked) =>
                                            setData('is_published', !!checked)
                                        }
                                    />
                                    <Label htmlFor="is_published">
                                        Publish assignment (make visible to residents)
                                    </Label>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Actions */}
                        <div className="flex justify-end gap-2">
                            <Button variant="outline" asChild>
                                <Link href="/assignments">Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creating...' : 'Create Assignment'}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

