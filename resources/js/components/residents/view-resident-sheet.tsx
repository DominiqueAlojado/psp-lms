import { ResidentOrganizations } from '@/components/residents/resident-organizations';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Building2, Mail, Phone, User } from 'lucide-react';

interface Organization {
    id: number;
    name: string;
    slug: string;
    type?: string;
    pivot?: {
        joined_at: string;
        is_active: boolean;
    };
}

interface Resident {
    id: number;
    uuid: string;
    full_name: string;
    full_name_with_middle_initial: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    email: string;
    contact_number: string | null;
    course: string;
    year_level: string;
    status: string;
    organization: Organization;
}

interface Props {
    open: boolean;
    resident: Resident | null;
    currentOrganizations: Organization[];
    availableOrganizations: Organization[];
    onClose: () => void;
    onRefresh: () => void;
}

export function ViewResidentSheet({
    open,
    resident,
    currentOrganizations,
    availableOrganizations,
    onClose,
    onRefresh,
}: Props) {
    if (!resident) return null;

    return (
        <Sheet open={open} onOpenChange={(open) => !open && onClose()}>
            <SheetContent className="overflow-y-auto p-0 sm:max-w-[700px]">
                <div className="p-8">
                    <SheetHeader className="pb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <SheetTitle>{resident.full_name}</SheetTitle>
                                <SheetDescription>
                                    {resident.year_level} - {resident.course}
                                </SheetDescription>
                            </div>
                            <Badge
                                variant={
                                    resident.status === 'active'
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {resident.status}
                            </Badge>
                        </div>
                    </SheetHeader>

                    <div className="space-y-6">
                        {/* Personal Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Personal Information</CardTitle>
                                <CardDescription>
                                    Resident's personal and contact details
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-start gap-3">
                                    <User className="mt-1 h-4 w-4 text-muted-foreground" />
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">
                                            Full Name
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {resident.full_name}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <Mail className="mt-1 h-4 w-4 text-muted-foreground" />
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">Email</p>
                                        <p className="text-sm text-muted-foreground">
                                            {resident.email}
                                        </p>
                                    </div>
                                </div>

                                {resident.contact_number && (
                                    <div className="flex items-start gap-3">
                                        <Phone className="mt-1 h-4 w-4 text-muted-foreground" />
                                        <div className="flex-1">
                                            <p className="text-sm font-medium">
                                                Contact Number
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {resident.contact_number}
                                            </p>
                                        </div>
                                    </div>
                                )}

                                <div className="flex items-start gap-3">
                                    <Building2 className="mt-1 h-4 w-4 text-muted-foreground" />
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">
                                            Home Institution
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {resident.organization.name}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Academic Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Academic Information</CardTitle>
                                <CardDescription>
                                    Course and year level details
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-sm font-medium">Course</p>
                                    <p className="text-sm text-muted-foreground">
                                        {resident.course}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-sm font-medium">
                                        Year Level
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {resident.year_level}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Organizations Management */}
                        <ResidentOrganizations
                            residentId={resident.id}
                            residentName={resident.full_name}
                            homeOrganizationId={resident.organization.id}
                            currentOrganizations={currentOrganizations}
                            availableOrganizations={availableOrganizations}
                            onUpdate={onRefresh}
                        />
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}

