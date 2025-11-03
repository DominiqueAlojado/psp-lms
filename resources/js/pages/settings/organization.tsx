import AlertError from '@/components/alert-error';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import organization from '@/routes/organization';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Building2, Edit, Trash2, Upload } from 'lucide-react';
import { type FormEvent, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Organization settings',
        href: organization.edit().url,
    },
];

interface Organization {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: string;
    logo: string | null;
    is_active: boolean;
}

interface Resident {
    id: number;
    uuid: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    name: string;
    email: string;
    contact_number: string;
    year_level: string;
    course: string;
    status: string;
}

interface Props {
    organization: Organization;
    residents: Resident[];
    errors: Record<string, string>;
    [key: string]: unknown;
}

export default function OrganizationSettings() {
    const { organization: org, residents, errors } = usePage<Props>().props;
    const logoInputRef = useRef<HTMLInputElement>(null);
    const [editingResident, setEditingResident] = useState<Resident | null>(
        null,
    );

    const handleLogoUpload = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);

        router.post(organization.logo.upload().url, formData, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                if (logoInputRef.current) {
                    logoInputRef.current.value = '';
                }
            },
        });
    };

    const handleLogoDelete = () => {
        if (confirm('Are you sure you want to delete the organization logo?')) {
            router.delete(organization.logo.delete().url, {
                preserveScroll: true,
                preserveState: true,
            });
        }
    };

    const getStatusBadge = (status: string) => {
        const variants: Record<
            string,
            'default' | 'secondary' | 'destructive'
        > = {
            active: 'default',
            inactive: 'secondary',
        };

        return (
            <Badge
                variant={variants[status] || 'secondary'}
                className="capitalize"
            >
                {status}
            </Badge>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organization Settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Organization Settings"
                        description="Manage your organization's information and view residents"
                    />
                    {/* Organization Details Card */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Organization Details</CardTitle>
                            <CardDescription>
                                Manage your organization's information
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Logo Section */}
                            <div className="space-y-4">
                                <Label>Organization Logo</Label>
                                <div className="flex items-start gap-4">
                                    <div className="flex h-24 w-24 items-center justify-center overflow-hidden rounded-lg border-2 border-dashed">
                                        {org.logo ? (
                                            <img
                                                src={`/storage/${org.logo}`}
                                                alt={org.name}
                                                className="h-full w-full object-cover"
                                            />
                                        ) : (
                                            <Building2 className="h-10 w-10 text-muted-foreground" />
                                        )}
                                    </div>
                                    <div className="flex-1 space-y-2">
                                        <form
                                            onSubmit={handleLogoUpload}
                                            className="flex gap-2"
                                        >
                                            <Input
                                                ref={logoInputRef}
                                                type="file"
                                                name="logo"
                                                accept="image/*"
                                                className="flex-1"
                                            />
                                            <Button type="submit" size="sm">
                                                <Upload className="mr-2 h-4 w-4" />
                                                Upload
                                            </Button>
                                        </form>
                                        {org.logo && (
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                onClick={handleLogoDelete}
                                            >
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                Delete Logo
                                            </Button>
                                        )}
                                        <p className="text-sm text-muted-foreground">
                                            Recommended: Square image, max 2MB
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Organization Form */}
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    const formData = new FormData(
                                        e.currentTarget,
                                    );
                                    router.patch(
                                        organization.update().url,
                                        Object.fromEntries(formData),
                                        {
                                            preserveScroll: true,
                                            preserveState: true,
                                        },
                                    );
                                }}
                            >
                                <div className="space-y-4">
                                    {Object.keys(errors).length > 0 && (
                                        <AlertError
                                            errors={Object.values(errors)}
                                        />
                                    )}

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="name">
                                                Organization Name
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                defaultValue={org.name}
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="slug">Slug</Label>
                                            <Input
                                                id="slug"
                                                value={org.slug}
                                                disabled
                                                className="bg-muted"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                Slug cannot be changed
                                            </p>
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="description">
                                            Description
                                        </Label>
                                        <Input
                                            id="description"
                                            name="description"
                                            defaultValue={org.description || ''}
                                        />
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="type">Type</Label>
                                            <Input
                                                id="type"
                                                value={org.type}
                                                disabled
                                                className="bg-muted capitalize"
                                            />
                                        </div>

                                        <div className="flex items-center space-x-2">
                                            <input
                                                type="hidden"
                                                name="is_active"
                                                value="0"
                                            />
                                            <input
                                                type="checkbox"
                                                id="is_active"
                                                name="is_active"
                                                defaultChecked={org.is_active}
                                                value="1"
                                                className="h-4 w-4 rounded border-gray-300"
                                            />
                                            <Label htmlFor="is_active">
                                                Organization is active
                                            </Label>
                                        </div>
                                    </div>

                                    <div className="flex justify-end">
                                        <Button type="submit">
                                            Save Changes
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Residents Table Card */}
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Residents ({residents.length})
                            </CardTitle>
                            <CardDescription>
                                List of all residents in this organization
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {residents.length === 0 ? (
                                <div className="py-8 text-center text-sm text-muted-foreground">
                                    No residents found in this organization
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Name</TableHead>
                                            <TableHead>Email</TableHead>
                                            <TableHead>Contact</TableHead>
                                            <TableHead>Year</TableHead>
                                            <TableHead>Course</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="w-[100px]">
                                                Actions
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {residents.map((resident) => (
                                            <TableRow key={resident.id}>
                                                <TableCell className="font-medium">
                                                    {resident.name}
                                                </TableCell>
                                                <TableCell>
                                                    {resident.email}
                                                </TableCell>
                                                <TableCell>
                                                    {resident.contact_number}
                                                </TableCell>
                                                <TableCell>
                                                    {resident.year_level}
                                                </TableCell>
                                                <TableCell className="max-w-[200px] truncate">
                                                    {resident.course}
                                                </TableCell>
                                                <TableCell>
                                                    {getStatusBadge(
                                                        resident.status,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            setEditingResident(
                                                                resident,
                                                            )
                                                        }
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Edit Resident Sheet */}
                <Sheet
                    open={!!editingResident}
                    onOpenChange={(open) => !open && setEditingResident(null)}
                >
                    <SheetContent className="overflow-y-auto p-0 sm:max-w-[600px]">
                        <div className="p-6">
                            <SheetHeader className="pb-6">
                                <SheetTitle>Edit Resident</SheetTitle>
                                <SheetDescription>
                                    Update resident information and account
                                    settings
                                </SheetDescription>
                            </SheetHeader>

                            {editingResident && (
                                <form
                                    key={editingResident.id}
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        const formData = new FormData(
                                            e.currentTarget,
                                        );
                                        const data =
                                            Object.fromEntries(formData);

                                        // Ensure all required fields are present
                                        const completeData = {
                                            first_name:
                                                data.first_name ||
                                                editingResident.first_name,
                                            middle_name:
                                                data.middle_name ||
                                                editingResident.middle_name ||
                                                '',
                                            last_name:
                                                data.last_name ||
                                                editingResident.last_name,
                                            email:
                                                data.email ||
                                                editingResident.email,
                                            contact_number:
                                                data.contact_number ||
                                                editingResident.contact_number,
                                            year_level:
                                                data.year_level ||
                                                editingResident.year_level,
                                            course:
                                                data.course ||
                                                editingResident.course,
                                            status:
                                                data.status ||
                                                editingResident.status,
                                            password: data.password || '',
                                            password_confirmation:
                                                data.password_confirmation ||
                                                '',
                                        };

                                        router.patch(
                                            `/settings/organization/residents/${editingResident.id}`,
                                            completeData,
                                            {
                                                preserveScroll: true,
                                                preserveState: true,
                                                onSuccess: () =>
                                                    setEditingResident(null),
                                            },
                                        );
                                    }}
                                >
                                    <div className="space-y-6">
                                        {Object.keys(errors).length > 0 && (
                                            <AlertError
                                                errors={Object.values(errors)}
                                            />
                                        )}

                                        <Tabs
                                            defaultValue="personal"
                                            className="w-full"
                                        >
                                            <TabsList className="mb-6 grid w-full grid-cols-2">
                                                <TabsTrigger value="personal">
                                                    Personal Data
                                                </TabsTrigger>
                                                <TabsTrigger value="account">
                                                    Account
                                                </TabsTrigger>
                                            </TabsList>

                                            {/* Tab 1: Personal Data */}
                                            <TabsContent
                                                value="personal"
                                                className="space-y-6 pt-2"
                                            >
                                                <div className="grid gap-4 md:grid-cols-2">
                                                    <div className="space-y-2">
                                                        <Label htmlFor="edit_first_name">
                                                            First Name
                                                        </Label>
                                                        <Input
                                                            id="edit_first_name"
                                                            name="first_name"
                                                            defaultValue={
                                                                editingResident.first_name
                                                            }
                                                        />
                                                    </div>

                                                    <div className="space-y-2">
                                                        <Label htmlFor="edit_middle_name">
                                                            Middle Name
                                                        </Label>
                                                        <Input
                                                            id="edit_middle_name"
                                                            name="middle_name"
                                                            defaultValue={
                                                                editingResident.middle_name ||
                                                                ''
                                                            }
                                                        />
                                                    </div>
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_last_name">
                                                        Last Name
                                                    </Label>
                                                    <Input
                                                        id="edit_last_name"
                                                        name="last_name"
                                                        defaultValue={
                                                            editingResident.last_name
                                                        }
                                                    />
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_email">
                                                        Email
                                                    </Label>
                                                    <Input
                                                        id="edit_email"
                                                        name="email"
                                                        type="email"
                                                        defaultValue={
                                                            editingResident.email
                                                        }
                                                    />
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_contact_number">
                                                        Contact Number
                                                    </Label>
                                                    <Input
                                                        id="edit_contact_number"
                                                        name="contact_number"
                                                        placeholder="09123456789 or +639123456789"
                                                        defaultValue={
                                                            editingResident.contact_number
                                                        }
                                                    />
                                                    <p className="text-xs text-muted-foreground">
                                                        Philippine mobile number
                                                        format (11 digits)
                                                    </p>
                                                </div>

                                                <div className="grid gap-4 md:grid-cols-2">
                                                    <div className="space-y-2">
                                                        <Label htmlFor="edit_year_level">
                                                            Year Level
                                                        </Label>
                                                        <select
                                                            id="edit_year_level"
                                                            name="year_level"
                                                            defaultValue={
                                                                editingResident.year_level
                                                            }
                                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                                        >
                                                            <option value="Pre Resident">
                                                                Pre Resident
                                                            </option>
                                                            <option value="First Year">
                                                                First Year
                                                            </option>
                                                            <option value="Second Year">
                                                                Second Year
                                                            </option>
                                                            <option value="Third Year">
                                                                Third Year
                                                            </option>
                                                            <option value="Fourth Year">
                                                                Fourth Year
                                                            </option>
                                                            <option value="Graduate">
                                                                Graduate
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <div className="space-y-2">
                                                        <Label htmlFor="edit_status">
                                                            Status
                                                        </Label>
                                                        <select
                                                            id="edit_status"
                                                            name="status"
                                                            defaultValue={
                                                                editingResident.status
                                                            }
                                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                                        >
                                                            <option value="active">
                                                                Active
                                                            </option>
                                                            <option value="inactive">
                                                                Inactive
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_course">
                                                        Course
                                                    </Label>
                                                    <Input
                                                        id="edit_course"
                                                        name="course"
                                                        defaultValue={
                                                            editingResident.course
                                                        }
                                                    />
                                                </div>
                                            </TabsContent>

                                            {/* Tab 2: Account */}
                                            <TabsContent
                                                value="account"
                                                className="space-y-6 pt-2"
                                            >
                                                <div className="rounded-lg border border-muted bg-muted/50 p-4">
                                                    <p className="text-sm text-muted-foreground">
                                                        Change the resident's
                                                        account password. Leave
                                                        blank to keep the
                                                        current password.
                                                    </p>
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_password">
                                                        New Password
                                                    </Label>
                                                    <Input
                                                        id="edit_password"
                                                        name="password"
                                                        type="password"
                                                        placeholder="Leave blank to keep current"
                                                        autoComplete="new-password"
                                                    />
                                                    <p className="text-xs text-muted-foreground">
                                                        Minimum 8 characters
                                                    </p>
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_password_confirmation">
                                                        Confirm Password
                                                    </Label>
                                                    <Input
                                                        id="edit_password_confirmation"
                                                        name="password_confirmation"
                                                        type="password"
                                                        placeholder="Confirm new password"
                                                        autoComplete="new-password"
                                                    />
                                                </div>
                                            </TabsContent>
                                        </Tabs>

                                        <div className="mt-8 flex justify-end gap-3 border-t pt-6">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() =>
                                                    setEditingResident(null)
                                                }
                                            >
                                                Cancel
                                            </Button>
                                            <Button type="submit">
                                                Save Changes
                                            </Button>
                                        </div>
                                    </div>
                                </form>
                            )}
                        </div>
                    </SheetContent>
                </Sheet>
            </SettingsLayout>
        </AppLayout>
    );
}
