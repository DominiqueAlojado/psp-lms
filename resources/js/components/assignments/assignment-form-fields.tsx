import { Checkbox } from '@/components/ui/checkbox';
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
import {
    ASSIGNMENT_TYPES,
    FILE_TYPES,
    YEAR_LEVELS,
} from './validation-schemas';

interface AssignmentFormData {
    title: string;
    description: string;
    instructions: string;
    assignment_type: string;
    target_year_levels: string[];
    max_score: number;
    cme_credits?: number | null;
    credit_type?: string | null;
    due_date: string;
    allow_late_submission: boolean;
    late_submission_until: string;
    late_penalty_percent: number;
    allow_resubmission: boolean;
    max_submissions: number;
    allowed_file_types: string[];
    max_file_size_mb: number;
    max_files: number;
    is_published: boolean;
}

interface AssignmentFormFieldsProps {
    data: AssignmentFormData;
    setData: <K extends keyof AssignmentFormData>(
        key: K,
        value: AssignmentFormData[K],
    ) => void;
    errors: Partial<Record<keyof AssignmentFormData, string>>;
    toggleYearLevel: (level: string) => void;
    toggleFileType: (type: string) => void;
}

export function AssignmentFormFields({
    data,
    setData,
    errors,
    toggleYearLevel,
    toggleFileType,
}: AssignmentFormFieldsProps) {
    return (
        <div className="p-4">
            {/* Basic Information */}
            <div className="space-y-4">
                <h3 className="text-lg font-semibold">Basic Information</h3>

                <div>
                    <Label htmlFor="title">
                        Title <span className="text-destructive">*</span>
                    </Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
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
                        Assignment Type{' '}
                        <span className="text-destructive">*</span>
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
                                <SelectItem key={type.value} value={type.value}>
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
                    <Label htmlFor="description">
                        Description <span className="text-destructive">*</span>
                    </Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
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
                    <Label htmlFor="instructions">
                        Instructions <span className="text-destructive">*</span>
                    </Label>
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
                    <Label>
                        Target Year Levels{' '}
                        <span className="text-destructive">*</span>
                    </Label>
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
                    {errors.target_year_levels && (
                        <p className="mt-1 text-sm text-destructive">
                            {errors.target_year_levels}
                        </p>
                    )}
                </div>
            </div>

            {/* Submission Settings */}
            <div className="mt-4 space-y-4">
                <h3 className="text-lg font-semibold">Submission Settings</h3>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <Label htmlFor="due_date">
                            Due Date <span className="text-destructive">*</span>
                        </Label>
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
                            Max Score{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="max_score"
                            type="number"
                            value={data.max_score}
                            onChange={(e) =>
                                setData('max_score', parseInt(e.target.value))
                            }
                            min={1}
                        />
                        {errors.max_score && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.max_score}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="cme_credits">
                            CME/CPD Credits (Optional)
                        </Label>
                        <Input
                            id="cme_credits"
                            type="number"
                            step="0.01"
                            value={data.cme_credits ?? ''}
                            onChange={(e) =>
                                setData(
                                    'cme_credits',
                                    e.target.value === ''
                                        ? null
                                        : parseFloat(e.target.value),
                                )
                            }
                            min={0}
                            max={100}
                            placeholder="0.00"
                        />
                        <p className="mt-1 text-xs text-muted-foreground">
                            Credits awarded when assignment is graded and passes
                            (60% minimum)
                        </p>
                        {errors.cme_credits && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.cme_credits}
                            </p>
                        )}
                    </div>

                    {data.cme_credits && data.cme_credits > 0 && (
                        <div>
                            <Label htmlFor="credit_type">
                                Credit Type{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Select
                                value={data.credit_type || 'cme'}
                                onValueChange={(value) =>
                                    setData('credit_type', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select credit type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="cme">CME (Continuing Medical Education)</SelectItem>
                                    <SelectItem value="cpd">CPD (Continuing Professional Development)</SelectItem>
                                </SelectContent>
                            </Select>
                            <p className="mt-1 text-xs text-muted-foreground">
                                CME: Clinical knowledge & skills | CPD: Professional development (leadership, ethics, etc.)
                            </p>
                            {errors.credit_type && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.credit_type}
                                </p>
                            )}
                        </div>
                    )}
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
            </div>

            {/* File Upload Settings */}
            <div className="mt-4 space-y-4">
                <h3 className="text-lg font-semibold">File Upload Settings</h3>

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
            </div>

            {/* Publishing */}
            <div className="mt-4 space-y-4">
                <h3 className="text-lg font-semibold">Publishing</h3>

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
            </div>
        </div>
    );
}
