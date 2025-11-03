import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import { z } from 'zod';
import { AccountInformationFields, PersonalInformationFields } from './resident-form-fields';
import { editAccountSchema, editPersonalSchema } from './validation-schemas';

interface Organization {
    id: number;
    name: string;
    slug: string;
}

interface Resident {
    id: number;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    email: string;
    contact_number: string | null;
    course: string;
    year_level: string;
    status: string;
    full_name: string;
    organization: Organization;
}

interface Props {
    open: boolean;
    resident: Resident | null;
    yearLevels: string[];
    statuses: string[];
    onClose: () => void;
}

export function EditResidentSheet({
    open,
    resident,
    yearLevels,
    statuses,
    onClose,
}: Props) {
    const [clientValidationErrors, setClientValidationErrors] = useState<Record<string, string>>({});
    const { errors: serverErrors } = usePage<{
        errors: Record<string, string>;
    }>().props;

    // Merge client-side and server-side errors
    const validationErrors = { ...clientValidationErrors, ...serverErrors };

    if (!resident) return null;

    const handleClose = () => {
        setClientValidationErrors({});
        onClose();
    };

    return (
        <Sheet open={open} onOpenChange={(open) => !open && handleClose()}>
            <SheetContent className="overflow-y-auto p-0 sm:max-w-[600px]">
                <div className="p-8">
                    <SheetHeader className="pb-6">
                        <SheetTitle>Edit Resident</SheetTitle>
                        <SheetDescription>
                            Update resident information for {resident.full_name}
                        </SheetDescription>
                    </SheetHeader>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            const formData = new FormData(e.currentTarget);
                            const data = Object.fromEntries(formData);

                            // Validate personal information
                            try {
                                editPersonalSchema.parse(data);
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
                                    return;
                                }
                            }

                            // Validate account information (optional password)
                            try {
                                editAccountSchema.parse(data);
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
                                    return;
                                }
                            }

                            // If validation passes, clear errors and submit
                            setClientValidationErrors({});
                            router.patch(`/residents/${resident.id}`, data, {
                                preserveScroll: true,
                                preserveState: true,
                                onSuccess: () => {
                                    toast.success('Resident updated successfully');
                                    handleClose();
                                },
                                onError: () => {
                                    toast.error('Failed to update resident');
                                },
                            });
                        }}
                    >
                        <Tabs defaultValue="personal" className="w-full">
                            <TabsList className="mb-6 grid w-full grid-cols-2">
                                <TabsTrigger value="personal">Personal Data</TabsTrigger>
                                <TabsTrigger value="account">Account</TabsTrigger>
                            </TabsList>

                            <TabsContent value="personal" className="space-y-6">
                                <PersonalInformationFields
                                    organizations={[resident.organization]}
                                    yearLevels={yearLevels}
                                    statuses={statuses}
                                    defaultValues={{
                                        first_name: resident.first_name,
                                        middle_name: resident.middle_name || '',
                                        last_name: resident.last_name,
                                        email: resident.email,
                                        contact_number: resident.contact_number || '',
                                        course: resident.course,
                                        year_level: resident.year_level,
                                        status: resident.status,
                                    }}
                                    validationErrors={validationErrors}
                                    showOrganization={false}
                                />
                            </TabsContent>

                            <TabsContent value="account" className="space-y-6">
                                <AccountInformationFields
                                    validationErrors={validationErrors}
                                    isOptional
                                />
                            </TabsContent>
                        </Tabs>

                        <div className="mt-6 flex justify-end gap-3 border-t pt-6">
                            <Button type="button" variant="outline" onClick={handleClose}>
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

