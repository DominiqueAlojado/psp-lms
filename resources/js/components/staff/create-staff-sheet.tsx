import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { StaffFormFields } from './staff-form-fields';
import { staffSchema } from './validation-schemas';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface Role {
    id: number;
    name: string;
}

interface Organization {
    id: number;
    name: string;
}

interface CreateStaffSheetProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    roles: Role[];
    organizations: Organization[];
}

export function CreateStaffSheet({
    open,
    onOpenChange,
    roles,
    organizations,
}: CreateStaffSheetProps) {
    const { errors: serverErrors } = usePage<any>().props;
    const [clientValidationErrors, setClientValidationErrors] = useState<
        Record<string, string>
    >({});

    const validationErrors = {
        ...clientValidationErrors,
        ...serverErrors,
    };

    const handleClose = () => {
        setClientValidationErrors({});
        onOpenChange(false);
    };

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        const formData = new FormData(e.currentTarget);

        // Extract roles
        const roles: number[] = [];
        formData.getAll('roles[]').forEach((value) => {
            const numValue = parseInt(value.toString());
            if (!isNaN(numValue)) {
                roles.push(numValue);
            }
        });

        // Extract organizations
        const organizations: number[] = [];
        formData.getAll('organizations[]').forEach((value) => {
            const numValue = parseInt(value.toString());
            if (!isNaN(numValue)) {
                organizations.push(numValue);
            }
        });

        // Build data object for Zod validation
        const zodData = {
            name: (formData.get('name') as string) || '',
            email: (formData.get('email') as string) || '',
            password: (formData.get('password') as string) || '',
            roles,
            organizations,
            current_organization_id: formData.get('current_organization_id')
                ? parseInt(formData.get('current_organization_id') as string)
                : null,
        };

        // Validate with Zod
        try {
            staffSchema.parse(zodData);
            setClientValidationErrors({});

            // Prepare data for Laravel
            const serverData = {
                ...zodData,
            };

            router.post('/staff', serverData, {
                preserveScroll: true,
                onSuccess: () => {
                    handleClose();
                },
                onError: (errors) => {
                    console.error('Server errors:', errors);
                },
            });
        } catch (error: any) {
            if (error.errors) {
                const errors: Record<string, string> = {};
                error.errors.forEach((err: any) => {
                    errors[err.path[0]] = err.message;
                });
                setClientValidationErrors(errors);
            }
        }
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="overflow-y-auto p-6 sm:max-w-[600px]">
                <SheetHeader className="mb-6">
                    <SheetTitle>Add New Staff Member</SheetTitle>
                    <SheetDescription>
                        Create a new staff account with roles and organization
                        access.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <StaffFormFields
                        validationErrors={validationErrors}
                        roles={roles}
                        organizations={organizations}
                    />

                    <div className="flex justify-end gap-3 border-t pt-6">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleClose}
                        >
                            Cancel
                        </Button>
                        <Button type="submit">Create Staff Member</Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>
    );
}

