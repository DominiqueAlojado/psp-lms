import AlertError from '@/components/alert-error';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import { z } from 'zod';
import { AccountInformationFields, PersonalInformationFields } from './resident-form-fields';
import { step1Schema, step2Schema } from './validation-schemas';

interface Organization {
    id: number;
    name: string;
}

interface Props {
    open: boolean;
    organizations: Organization[];
    yearLevels: string[];
    statuses: string[];
    errors: Record<string, string>;
    onClose: () => void;
}

export function CreateResidentSheet({
    open,
    organizations,
    yearLevels,
    statuses,
    errors,
    onClose,
}: Props) {
    const [currentStep, setCurrentStep] = useState(1);
    const [formData, setFormData] = useState<Record<string, string>>({});
    const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});

    const handleClose = () => {
        setCurrentStep(1);
        setFormData({});
        setValidationErrors({});
        onClose();
    };

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        console.log('Form submitted, current step:', currentStep);

        const currentFormData = new FormData(e.currentTarget);
        const currentData = Object.fromEntries(currentFormData);

        console.log('Current form data:', currentData);

        if (currentStep === 1) {
            // Validate step 1 data
            try {
                step1Schema.parse(currentData);
                setValidationErrors({});
                // Save step 1 data and move to step 2
                const dataToSave: Record<string, string> = {};
                Object.entries(currentData).forEach(([key, value]) => {
                    dataToSave[key] = typeof value === 'string' ? value : '';
                });
                setFormData((prev) => ({ ...prev, ...dataToSave }));
                setCurrentStep(2);
            } catch (error) {
                if (error instanceof z.ZodError) {
                    const errors: Record<string, string> = {};
                    error.issues.forEach((err) => {
                        if (err.path[0]) {
                            errors[err.path[0].toString()] = err.message;
                        }
                    });
                    setValidationErrors(errors);
                }
            }
            return;
        }

        // Step 2: Validate and submit all data
        const dataToSave: Record<string, string> = {};
        Object.entries(currentData).forEach(([key, value]) => {
            dataToSave[key] = typeof value === 'string' ? value : '';
        });
        const allData = { ...formData, ...dataToSave };

        console.log('All form data being submitted:', allData);

        try {
            step2Schema.parse(currentData);
            setValidationErrors({});

            console.log('Validation passed, submitting to server...');

            router.post('/residents', allData, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Resident created successfully');
                    handleClose();
                },
                onError: () => {
                    toast.error('Failed to create resident. Please check the form.');
                },
            });
        } catch (error) {
            if (error instanceof z.ZodError) {
                console.error('Step 2 Validation failed:', error.issues);
                const errors: Record<string, string> = {};
                error.issues.forEach((err) => {
                    if (err.path[0]) {
                        errors[err.path[0].toString()] = err.message;
                    }
                });
                setValidationErrors(errors);
            }
        }
    };

    const handleBack = (e: React.MouseEvent<HTMLButtonElement>) => {
        const form = e.currentTarget.closest('form');
        if (form) {
            const currentFormData = new FormData(form);
            const currentData = Object.fromEntries(currentFormData);
            const dataToSave: Record<string, string> = {};
            Object.entries(currentData).forEach(([key, value]) => {
                dataToSave[key] = typeof value === 'string' ? value : '';
            });
            setFormData((prev) => ({ ...prev, ...dataToSave }));
        }
        setValidationErrors({});
        setCurrentStep(1);
    };

    return (
        <Sheet open={open} onOpenChange={(open) => !open && handleClose()}>
            <SheetContent className="overflow-y-auto p-0 sm:max-w-[600px]">
                <div className="p-8">
                    <SheetHeader className="pb-6">
                        <SheetTitle>Add New Resident</SheetTitle>
                        <SheetDescription>
                            Create a new resident and their user account
                        </SheetDescription>
                    </SheetHeader>

                    {/* Step Indicator */}
                    <div className="mb-6 flex items-center gap-2">
                        <div
                            className={`flex h-8 w-8 items-center justify-center rounded-full ${
                                currentStep === 1
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground'
                            }`}
                        >
                            1
                        </div>
                        <div className="h-[2px] flex-1 bg-muted" />
                        <div
                            className={`flex h-8 w-8 items-center justify-center rounded-full ${
                                currentStep === 2
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground'
                            }`}
                        >
                            2
                        </div>
                    </div>

                    <form onSubmit={handleSubmit}>
                        {/* Step 1: Personal Data */}
                        {currentStep === 1 && (
                            <div className="space-y-6">
                                <h3 className="text-lg font-semibold">
                                    Step 1: Personal Information
                                </h3>

                                {(Object.keys(errors).length > 0 ||
                                    Object.keys(validationErrors).length > 0) && (
                                    <AlertError
                                        errors={
                                            [
                                                ...Object.values(errors),
                                                ...Object.values(validationErrors),
                                            ] as string[]
                                        }
                                    />
                                )}

                                <PersonalInformationFields
                                    organizations={organizations}
                                    yearLevels={yearLevels}
                                    statuses={statuses}
                                    defaultValues={formData}
                                    validationErrors={validationErrors}
                                />
                            </div>
                        )}

                        {/* Step 2: Account */}
                        {currentStep === 2 && (
                            <div className="space-y-6">
                                <h3 className="text-lg font-semibold">
                                    Step 2: Account Information
                                </h3>

                                {(Object.keys(errors).length > 0 ||
                                    Object.keys(validationErrors).length > 0) && (
                                    <AlertError
                                        errors={
                                            [
                                                ...Object.values(errors),
                                                ...Object.values(validationErrors),
                                            ] as string[]
                                        }
                                    />
                                )}

                                <AccountInformationFields
                                    defaultValues={formData}
                                    validationErrors={validationErrors}
                                />
                            </div>
                        )}

                        {/* Navigation Buttons */}
                        <div className="mt-6 flex justify-between gap-3 border-t pt-6">
                            {currentStep === 1 ? (
                                <>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={handleClose}
                                    >
                                        Cancel
                                    </Button>
                                    <Button type="submit">Next</Button>
                                </>
                            ) : (
                                <>
                                    <Button type="button" variant="outline" onClick={handleBack}>
                                        Back
                                    </Button>
                                    <Button type="submit">Create Resident</Button>
                                </>
                            )}
                        </div>
                    </form>
                </div>
            </SheetContent>
        </Sheet>
    );
}

