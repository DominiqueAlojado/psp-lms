import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { z } from 'zod';
import { AssignmentFormFields } from './assignment-form-fields';
import { assignmentSchema } from './validation-schemas';

interface Assignment {
    id: number;
    title: string;
    description?: string | null;
    instructions?: string | null;
    assignment_type: string;
    target_year_levels?: string[] | null;
    max_score?: number;
    cme_credits?: number | null;
    credit_type?: string | null;
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
}

interface Props {
    open: boolean;
    assignment: Assignment | null;
    onClose: () => void;
}

export function EditAssignmentSheet({ open, assignment, onClose }: Props) {
    const [data, setData] = useState({
        title: assignment?.title || '',
        description: assignment?.description || '',
        instructions: assignment?.instructions || '',
        assignment_type: assignment?.assignment_type || 'case_report',
        target_year_levels: (assignment?.target_year_levels || []) as string[],
        max_score: assignment?.max_score || 100,
        cme_credits: assignment?.cme_credits ?? null,
        credit_type: assignment?.credit_type ?? 'cme',
        due_date: assignment?.due_date || '',
        allow_late_submission: assignment?.allow_late_submission || false,
        late_submission_until: assignment?.late_submission_until || '',
        late_penalty_percent: assignment?.late_penalty_percent || 10,
        allow_resubmission: assignment?.allow_resubmission || false,
        max_submissions: assignment?.max_submissions || 1,
        allowed_file_types: (assignment?.allowed_file_types || [
            'pdf',
            'docx',
            'pptx',
        ]) as string[],
        max_file_size_mb: assignment?.max_file_size_mb || 10,
        max_files: assignment?.max_files || 5,
        is_published: assignment?.is_published || false,
    });

    const [clientValidationErrors, setClientValidationErrors] = useState<
        Record<string, string>
    >({});
    const [processing, setProcessing] = useState(false);
    const { errors: serverErrors } = usePage<{
        errors: Record<string, string>;
    }>().props;

    // Merge client-side and server-side errors
    const errors = { ...clientValidationErrors, ...serverErrors };

    // Update data when assignment prop changes
    useEffect(() => {
        if (assignment) {
            setData({
                title: assignment.title,
                description: assignment.description || '',
                instructions: assignment.instructions || '',
                assignment_type: assignment.assignment_type,
                target_year_levels: (assignment.target_year_levels ||
                    []) as string[],
                max_score: assignment.max_score || 100,
                cme_credits: assignment.cme_credits ?? null,
                credit_type: assignment.credit_type ?? 'cme',
                due_date: assignment.due_date || '',
                allow_late_submission: assignment.allow_late_submission || false,
                late_submission_until: assignment.late_submission_until || '',
                late_penalty_percent: assignment.late_penalty_percent || 10,
                allow_resubmission: assignment.allow_resubmission || false,
                max_submissions: assignment.max_submissions || 1,
                allowed_file_types: (assignment.allowed_file_types || [
                    'pdf',
                    'docx',
                    'pptx',
                ]) as string[],
                max_file_size_mb: assignment.max_file_size_mb || 10,
                max_files: assignment.max_files || 5,
                is_published: assignment.is_published,
            });
        }
    }, [assignment]);

    const handleClose = () => {
        setClientValidationErrors({});
        onClose();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!assignment) return;

        // Validate with Zod
        try {
            assignmentSchema.parse(data);
            setClientValidationErrors({});

            setProcessing(true);

            router.patch(`/assignments/${assignment.id}`, data, {
                preserveScroll: true,
                onSuccess: () => {
                    handleClose();
                },
                onError: (errors) => {
                    if (
                        errors &&
                        typeof errors === 'object' &&
                        !Object.keys(errors).length
                    ) {
                        window.location.reload();
                    } else {
                        toast.error('Please check the form for errors');
                    }
                },
                onFinish: () => {
                    setProcessing(false);
                },
            });
        } catch (error) {
            if (error instanceof z.ZodError) {
                const errors: Record<string, string> = {};
                error.issues.forEach((err) => {
                    if (err.path[0]) {
                        errors[err.path[0].toString()] = err.message;
                    }
                });
                setClientValidationErrors(errors);
                toast.error('Please check the form for errors');
            }
        }
    };

    const updateData = <K extends keyof typeof data>(
        key: K,
        value: (typeof data)[K],
    ) => {
        setData((prev) => ({ ...prev, [key]: value }));
    };

    const toggleYearLevel = (level: string) => {
        if (data.target_year_levels.includes(level)) {
            setData((prev) => ({
                ...prev,
                target_year_levels: prev.target_year_levels.filter(
                    (l) => l !== level,
                ),
            }));
        } else {
            setData((prev) => ({
                ...prev,
                target_year_levels: [...prev.target_year_levels, level],
            }));
        }
    };

    const toggleFileType = (type: string) => {
        if (data.allowed_file_types.includes(type)) {
            setData((prev) => ({
                ...prev,
                allowed_file_types: prev.allowed_file_types.filter(
                    (t) => t !== type,
                ),
            }));
        } else {
            setData((prev) => ({
                ...prev,
                allowed_file_types: [...prev.allowed_file_types, type],
            }));
        }
    };

    if (!assignment) return null;

    return (
        <Sheet open={open} onOpenChange={(open) => !open && handleClose()}>
            <SheetContent className="overflow-y-auto p-0 sm:max-w-[700px]">
                <div className="p-6">
                    <SheetHeader className="pb-6 text-left">
                        <SheetTitle>Edit Assignment</SheetTitle>
                        <SheetDescription>
                            Update assignment details
                        </SheetDescription>
                    </SheetHeader>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <AssignmentFormFields
                            data={data}
                            setData={updateData}
                            errors={errors}
                            toggleYearLevel={toggleYearLevel}
                            toggleFileType={toggleFileType}
                        />
                        <div className="flex justify-end gap-2 border-t pt-6">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleClose}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Updating...' : 'Update Assignment'}
                            </Button>
                        </div>
                    </form>
                </div>
            </SheetContent>
        </Sheet>
    );
}

