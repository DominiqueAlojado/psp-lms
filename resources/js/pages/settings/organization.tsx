import AlertError from '@/components/alert-error';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import organization from '@/routes/organization';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, router, usePage } from '@inertiajs/react';
import { Building2, Trash2, Upload } from 'lucide-react';
import { type FormEvent, useRef } from 'react';

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
    name: string;
    email: string;
    contact_number: string;
    year_level: number;
    course: string;
    status: string;
}

interface Props {
    organization: Organization;
    residents: Resident[];
}

export default function OrganizationSettings() {
    const { organization: org, residents } = usePage<Props>().props;
    const logoInputRef = useRef<HTMLInputElement>(null);

    const handleLogoUpload = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);

        router.post(organization.logo.upload().url, formData, {
            preserveScroll: true,
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
            });
        }
    };

    const getStatusBadge = (status: string) => {
        const variants: Record<string, 'default' | 'secondary' | 'destructive'> = {
            active: 'default',
            inactive: 'secondary',
            graduated: 'secondary',
        };

        return (
            <Badge variant={variants[status] || 'secondary'} className="capitalize">
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
                                    <form onSubmit={handleLogoUpload} className="flex gap-2">
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
                        <Form action={organization.update().url} method="patch">
                            {({ errors, processing }) => (
                                <div className="space-y-4">
                                    {Object.keys(errors).length > 0 && (
                                        <AlertError errors={Object.values(errors)} />
                                    )}

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="name">Organization Name</Label>
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
                                        <Label htmlFor="description">Description</Label>
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
                                                type="checkbox"
                                                id="is_active"
                                                name="is_active"
                                                defaultChecked={org.is_active}
                                                value="1"
                                                className="h-4 w-4 rounded border-gray-300"
                                            />
                                            <Label htmlFor="is_active">Organization is active</Label>
                                        </div>
                                    </div>

                                    <div className="flex justify-end">
                                        <Button type="submit" disabled={processing}>
                                            Save Changes
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                {/* Residents Table Card */}
                <Card>
                    <CardHeader>
                        <CardTitle>Residents ({residents.length})</CardTitle>
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
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {residents.map((resident) => (
                                        <TableRow key={resident.id}>
                                            <TableCell className="font-medium">
                                                {resident.name}
                                            </TableCell>
                                            <TableCell>{resident.email}</TableCell>
                                            <TableCell>{resident.contact_number}</TableCell>
                                            <TableCell>Year {resident.year_level}</TableCell>
                                            <TableCell className="max-w-[200px] truncate">
                                                {resident.course}
                                            </TableCell>
                                            <TableCell>
                                                {getStatusBadge(resident.status)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}

