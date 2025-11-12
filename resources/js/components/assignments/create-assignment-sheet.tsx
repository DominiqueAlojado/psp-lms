import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import { z } from 'zod';
import { AssignmentFormFields } from './assignment-form-fields';
import { assignmentSchema } from './validation-schemas';

interface Props {
    open: boolean;
    onClose: () => void;
}

export function CreateAssignmentSheet({ open, onClose }: Props) {
    const [data, setData] = useState({
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

    const [clientValidationErrors, setClientValidationErrors] = useState<
        Record<string, string>
    >({});
    const [processing, setProcessing] = useState(false);
    const { errors: serverErrors } = usePage<{
        errors: Record<string, string>;
    }>().props;

    // Merge client-side and server-side errors
    const errors = { ...clientValidationErrors, ...serverErrors };

    const handleClose = () => {
        setData({
            title: '',
            description: '',
            instructions: '',
            assignment_type: 'case_report',
            target_year_levels: [],
            max_score: 100,
            due_date: '',
            allow_late_submission: false,
            late_submission_until: '',
            late_penalty_percent: 10,
            allow_resubmission: false,
            max_submissions: 1,
            allowed_file_types: ['pdf', 'docx', 'pptx'],
            max_file_size_mb: 10,
            max_files: 5,
            is_published: false,
        });
        setClientValidationErrors({});
        onClose();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validate with Zod
        try {
            assignmentSchema.parse(data);
            setClientValidationErrors({});

            setProcessing(true);

            router.post('/assignments', data, {
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

    return (
        <Sheet open={open} onOpenChange={(open) => !open && handleClose()}>
            <SheetContent className="overflow-y-auto p-0 sm:max-w-[700px]">
                <div className="p-4">
                    <SheetHeader className="pb-6 text-left">
                        <SheetTitle>Create Assignment1</SheetTitle>
                        <SheetDescription>
                            Create a new assignment for residents
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
                                {processing
                                    ? 'Creating...'
                                    : 'Create Assignment'}
                            </Button>
                        </div>
                    </form>
                </div>
            </SheetContent>
        </Sheet>
    );
}
