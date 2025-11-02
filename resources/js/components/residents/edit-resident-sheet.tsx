import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { AccountInformationFields, PersonalInformationFields } from './resident-form-fields';

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
    if (!resident) return null;

    return (
        <Sheet open={open} onOpenChange={(open) => !open && onClose()}>
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
                            router.patch(`/residents/${resident.id}`, Object.fromEntries(formData), {
                                preserveScroll: true,
                                preserveState: true,
                                onSuccess: () => {
                                    toast.success('Resident updated successfully');
                                    onClose();
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
                                    showOrganization={false}
                                />
                            </TabsContent>

                            <TabsContent value="account" className="space-y-6">
                                <AccountInformationFields isOptional />
                            </TabsContent>
                        </Tabs>

                        <div className="mt-6 flex justify-end gap-3 border-t pt-6">
                            <Button type="button" variant="outline" onClick={onClose}>
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

