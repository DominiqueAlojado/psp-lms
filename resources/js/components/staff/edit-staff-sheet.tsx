import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { StaffFormFields } from './staff-form-fields';
import { staffEditSchema } from './validation-schemas';
import { router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

interface Role {
    id: number;
    name: string;
}

interface Organization {
    id: number;
    name: string;
}

interface Staff {
    id: number;
    uuid: string;
    name: string;
    email: string;
    roles: string[];
    primary_role: string;
    current_organization: string;
    organizations_count: number;
}

interface EditStaffSheetProps {
    staff: Staff | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    roles: Role[];
    organizations: Organization[];
}

export function EditStaffSheet({
    staff,
    open,
    onOpenChange,
    roles,
    organizations,
}: EditStaffSheetProps) {
    const { errors: serverErrors } = usePage<any>().props;
    const [clientValidationErrors, setClientValidationErrors] = useState<
        Record<string, string>
    >({});
    const [staffDetails, setStaffDetails] = useState<any>(null);
    const [loading, setLoading] = useState(false);

    const validationErrors = {
        ...clientValidationErrors,
        ...serverErrors,
    };

    useEffect(() => {
        if (staff && open) {
            setLoading(true);
            fetch(`/staff/${staff.id}`)
                .then((res) => res.json())
                .then((data) => {
                    setStaffDetails(data.staff);
                    setLoading(false);
                })
                .catch((error) => {
                    console.error('Error fetching staff details:', error);
                    setLoading(false);
                });
        }
    }, [staff, open]);

    const handleClose = () => {
        setClientValidationErrors({});
        setStaffDetails(null);
        onOpenChange(false);
    };

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        if (!staff) return;

        const formData = new FormData(e.currentTarget);

        // Extract roles
        const rolesArray: number[] = [];
        formData.getAll('roles[]').forEach((value) => {
            const numValue = parseInt(value.toString());
            if (!isNaN(numValue)) {
                rolesArray.push(numValue);
            }
        });

        // Extract organizations
        const organizationsArray: number[] = [];
        formData.getAll('organizations[]').forEach((value) => {
            const numValue = parseInt(value.toString());
            if (!isNaN(numValue)) {
                organizationsArray.push(numValue);
            }
        });

        // Build data object for Zod validation
        const zodData = {
            name: (formData.get('name') as string) || '',
            email: (formData.get('email') as string) || '',
            password: (formData.get('password') as string) || '',
            roles: rolesArray,
            organizations: organizationsArray,
            current_organization_id: formData.get('current_organization_id')
                ? parseInt(formData.get('current_organization_id') as string)
                : null,
        };

        // Validate with Zod
        try {
            staffEditSchema.parse(zodData);
            setClientValidationErrors({});

            // Prepare data for Laravel
            const serverData = {
                ...zodData,
            };

            router.patch(`/staff/${staff.id}`, serverData, {
                preserveScroll: true,
                preserveState: true,
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

    if (!staff) return null;

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="overflow-y-auto p-6 sm:max-w-[600px]">
                <SheetHeader className="mb-6">
                    <SheetTitle>Edit Staff Member</SheetTitle>
                    <SheetDescription>
                        Update staff account information, roles, and
                        organization access.
                    </SheetDescription>
                </SheetHeader>

                {loading ? (
                    <div className="py-8 text-center">
                        <p className="text-sm text-muted-foreground">
                            Loading...
                        </p>
                    </div>
                ) : (
                    <form
                        onSubmit={handleSubmit}
                        className="space-y-6"
                        key={staff.id}
                    >
                        <StaffFormFields
                            defaultValues={
                                staffDetails || {
                                    name: staff.name,
                                    email: staff.email,
                                }
                            }
                            validationErrors={validationErrors}
                            roles={roles}
                            organizations={organizations}
                            isEdit={true}
                        />

                        <div className="flex justify-end gap-3 border-t pt-6">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleClose}
                            >
                                Cancel
                            </Button>
                            <Button type="submit">Update Staff Member</Button>
                        </div>
                    </form>
                )}
            </SheetContent>
        </Sheet>
    );
}

