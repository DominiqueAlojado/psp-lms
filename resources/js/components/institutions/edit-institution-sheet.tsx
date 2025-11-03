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
import { InstitutionFormFields } from './institution-form-fields';
import { institutionSchema } from './validation-schemas';

interface Institution {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: string;
    is_active: boolean;
}

interface Props {
    open: boolean;
    institution: Institution | null;
    onClose: () => void;
}

export function EditInstitutionSheet({ open, institution, onClose }: Props) {
    const [clientValidationErrors, setClientValidationErrors] = useState<
        Record<string, string>
    >({});
    const { errors: serverErrors } = usePage<{
        errors: Record<string, string>;
    }>().props;

    // Merge client-side and server-side errors
    const validationErrors = { ...clientValidationErrors, ...serverErrors };

    if (!institution) return null;

    const handleClose = () => {
        setClientValidationErrors({});
        onClose();
    };

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        const formData = new FormData(e.currentTarget);

        // Build data object with proper types for Zod validation
        const zodData = {
            name: formData.get('name') as string,
            type: formData.get('type') as string,
            description: (formData.get('description') as string) || '',
            is_active: formData.get('is_active') === 'on',
        };

        console.log('Form data for validation:', zodData);

        // Validate with Zod
        try {
            institutionSchema.parse(zodData);
            setClientValidationErrors({});

            console.log('Validation passed, submitting to server...');

            // Prepare data for Laravel (convert boolean to 1/0)
            const serverData = {
                ...zodData,
                is_active: zodData.is_active ? 1 : 0,
            };

            router.patch(`/institutions/${institution.id}`, serverData, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.success('Institution updated successfully');
                    handleClose();
                },
                onError: (errors) => {
                    console.error('Server errors:', errors);
                    toast.error('Failed to update institution. Check form.');
                },
            });
        } catch (error) {
            if (error instanceof z.ZodError) {
                console.error('Zod validation errors:', error.issues);
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

    return (
        <Sheet open={open} onOpenChange={(open) => !open && handleClose()}>
            <SheetContent className="overflow-y-auto p-0 sm:max-w-[500px]">
                <div className="p-8">
                    <SheetHeader className="pb-6">
                        <SheetTitle>Edit Institution</SheetTitle>
                        <SheetDescription>
                            Update institution information for{' '}
                            {institution.name}
                        </SheetDescription>
                    </SheetHeader>

                    <form
                        onSubmit={handleSubmit}
                        className="space-y-6"
                        key={institution.id}
                    >
                        <InstitutionFormFields
                            defaultValues={{
                                name: institution.name,
                                description: institution.description || '',
                                type: institution.type,
                                is_active: institution.is_active,
                            }}
                            validationErrors={validationErrors}
                        />

                        <div className="flex justify-end gap-3 border-t pt-6">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleClose}
                            >
                                Cancel
                            </Button>
                            <Button type="submit">Save Changes</Button>
                        </div>
                    </form>
                </div>
            </SheetContent>
        </Sheet>
    );
}
