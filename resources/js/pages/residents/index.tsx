import AlertError from '@/components/alert-error';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Edit, Eye, Filter, Search, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Residents',
        href: '/residents',
    },
];

interface Organization {
    id: number;
    name: string;
    slug: string;
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

interface PaginatedResidents {
    data: Resident[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    residents: PaginatedResidents;
    organizations: Organization[];
    filters: {
        search?: string;
        organization_id?: number;
        year_level?: string;
        status?: string;
        course?: string;
    };
    yearLevels: string[];
    statuses: string[];
    courses: string[];
}

export default function ResidentsIndex({ residents, organizations, filters, yearLevels, statuses, courses }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [showFilters, setShowFilters] = useState(false);
    const [localFilters, setLocalFilters] = useState(filters);
    const [editingResident, setEditingResident] = useState<Resident | null>(null);
    const { errors } = usePage<any>().props;

    // Debounced search
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (search !== filters.search) {
                applyFilters({ ...localFilters, search });
            }
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [search]);

    const applyFilters = (newFilters: typeof filters) => {
        router.get('/residents', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const updateFilter = (key: string, value: any) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        applyFilters(newFilters);
    };

    const clearFilters = () => {
        setSearch('');
        setLocalFilters({});
        router.get('/residents', {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const hasActiveFilters = Object.keys(filters).some(key => filters[key as keyof typeof filters]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Residents" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <HeadingSmall
                    title="Residents"
                    description="Search and manage all residents across organizations"
                />

                {/* Search and Filter Bar */}
                <Card>
                    <CardContent className="p-8 space-y-6">
                        <div className="flex gap-4">
                            {/* Search Input */}
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Search by name, email, or contact number..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>

                            {/* Filter Toggle Button */}
                            <Button
                                variant={showFilters ? 'default' : 'outline'}
                                onClick={() => setShowFilters(!showFilters)}
                            >
                                <Filter className="mr-2 h-4 w-4" />
                                Filters
                                {hasActiveFilters && (
                                    <Badge variant="secondary" className="ml-2">
                                        {Object.keys(filters).filter(k => filters[k as keyof typeof filters]).length}
                                    </Badge>
                                )}
                            </Button>

                            {hasActiveFilters && (
                                <Button variant="ghost" onClick={clearFilters}>
                                    <X className="mr-2 h-4 w-4" />
                                    Clear
                                </Button>
                            )}
                        </div>

                        {/* Filter Options */}
                        {showFilters && (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t">
                                {/* Organization Filter */}
                                <div>
                                    <label className="text-sm font-medium mb-2 block">Organization</label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        value={localFilters.organization_id || ''}
                                        onChange={(e) => updateFilter('organization_id', e.target.value || undefined)}
                                    >
                                        <option value="">All Organizations</option>
                                        {organizations.map((org) => (
                                            <option key={org.id} value={org.id}>
                                                {org.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Year Level Filter */}
                                <div>
                                    <label className="text-sm font-medium mb-2 block">Year Level</label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        value={localFilters.year_level || ''}
                                        onChange={(e) => updateFilter('year_level', e.target.value || undefined)}
                                    >
                                        <option value="">All Year Levels</option>
                                        {yearLevels.map((level) => (
                                            <option key={level} value={level}>
                                                {level}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Status Filter */}
                                <div>
                                    <label className="text-sm font-medium mb-2 block">Status</label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        value={localFilters.status || ''}
                                        onChange={(e) => updateFilter('status', e.target.value || undefined)}
                                    >
                                        <option value="">All Statuses</option>
                                        {statuses.map((status) => (
                                            <option key={status} value={status}>
                                                {status.charAt(0).toUpperCase() + status.slice(1)}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Course Filter */}
                                <div>
                                    <label className="text-sm font-medium mb-2 block">Course</label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        value={localFilters.course || ''}
                                        onChange={(e) => updateFilter('course', e.target.value || undefined)}
                                    >
                                        <option value="">All Courses</option>
                                        {courses.map((course) => (
                                            <option key={course} value={course}>
                                                {course}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        )}

                        {/* Active Filter Chips */}
                        {hasActiveFilters && (
                            <div className="flex flex-wrap gap-2">
                                {filters.search && (
                                    <Badge variant="secondary" className="gap-1">
                                        Search: {filters.search}
                                        <X
                                            className="h-3 w-3 cursor-pointer"
                                            onClick={() => {
                                                setSearch('');
                                                updateFilter('search', undefined);
                                            }}
                                        />
                                    </Badge>
                                )}
                                {filters.organization_id && (
                                    <Badge variant="secondary" className="gap-1">
                                        Org: {organizations.find(o => o.id === Number(filters.organization_id))?.name}
                                        <X
                                            className="h-3 w-3 cursor-pointer"
                                            onClick={() => updateFilter('organization_id', undefined)}
                                        />
                                    </Badge>
                                )}
                                {filters.year_level && (
                                    <Badge variant="secondary" className="gap-1">
                                        Year: {filters.year_level}
                                        <X
                                            className="h-3 w-3 cursor-pointer"
                                            onClick={() => updateFilter('year_level', undefined)}
                                        />
                                    </Badge>
                                )}
                                {filters.status && (
                                    <Badge variant="secondary" className="gap-1">
                                        Status: {filters.status}
                                        <X
                                            className="h-3 w-3 cursor-pointer"
                                            onClick={() => updateFilter('status', undefined)}
                                        />
                                    </Badge>
                                )}
                                {filters.course && (
                                    <Badge variant="secondary" className="gap-1">
                                        Course: {filters.course}
                                        <X
                                            className="h-3 w-3 cursor-pointer"
                                            onClick={() => updateFilter('course', undefined)}
                                        />
                                    </Badge>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Results Table */}
                <Card>
                    <CardContent className="p-8">
                        <div className="space-y-6">
                            {/* Results Count */}
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Showing {residents.from || 0} to {residents.to || 0} of {residents.total} residents
                                </p>
                            </div>

                            {/* Table */}
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="py-4">Name</TableHead>
                                        <TableHead className="py-4">Email</TableHead>
                                        <TableHead className="py-4">Organization</TableHead>
                                        <TableHead className="py-4">Year Level</TableHead>
                                        <TableHead className="py-4">Status</TableHead>
                                        <TableHead className="w-[100px] py-4">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {residents.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                                                No residents found. Try adjusting your filters.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        residents.data.map((resident) => (
                                            <TableRow key={resident.id}>
                                                <TableCell className="font-medium py-4">
                                                    {resident.full_name_with_middle_initial}
                                                </TableCell>
                                                <TableCell className="py-4">{resident.email}</TableCell>
                                                <TableCell className="py-4">
                                                    <span className="text-sm text-muted-foreground">
                                                        {resident.organization.name}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="py-4">{resident.year_level}</TableCell>
                                                <TableCell className="py-4">
                                                    <Badge variant={resident.status === 'active' ? 'default' : 'secondary'}>
                                                        {resident.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="py-4">
                                                    <div className="flex gap-2">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => setEditingResident(resident)}
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                        <Link href={`/residents/${resident.id}`}>
                                                            <Button variant="ghost" size="sm">
                                                                <Eye className="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>

                            {/* Pagination */}
                            {residents.last_page > 1 && (
                                <div className="flex items-center justify-center gap-2">
                                    {residents.links.map((link, index) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? 'default' : 'outline'}
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => {
                                                if (link.url) {
                                                    const url = new URL(link.url);
                                                    const page = url.searchParams.get('page');
                                                    router.get('/residents', { ...filters, page }, {
                                                        preserveState: true,
                                                        preserveScroll: true,
                                                    });
                                                }
                                            }}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Edit Resident Sheet */}
            <Sheet open={!!editingResident} onOpenChange={(open) => !open && setEditingResident(null)}>
                <SheetContent className="sm:max-w-[600px] overflow-y-auto p-0">
                    {editingResident && (
                        <div className="p-8">
                            <SheetHeader className="pb-6">
                                <SheetTitle>Edit Resident</SheetTitle>
                                <SheetDescription>
                                    Update resident information for {editingResident.full_name}
                                </SheetDescription>
                            </SheetHeader>

                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    const formData = new FormData(e.currentTarget);
                                    router.patch(
                                        `/residents/${editingResident.id}`,
                                        Object.fromEntries(formData),
                                        {
                                            preserveScroll: true,
                                            preserveState: true,
                                            onSuccess: () => setEditingResident(null),
                                        }
                                    );
                                }}
                            >
                                <Tabs defaultValue="personal" className="w-full">
                                    <TabsList className="grid w-full grid-cols-2 mb-6">
                                        <TabsTrigger value="personal">Personal Data</TabsTrigger>
                                        <TabsTrigger value="account">Account</TabsTrigger>
                                    </TabsList>

                                    {/* Personal Data Tab */}
                                    <TabsContent value="personal" className="space-y-6">
                                        {Object.keys(errors).length > 0 && (
                                            <AlertError errors={Object.values(errors)} />
                                        )}

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_first_name">First Name</Label>
                                            <Input
                                                id="edit_first_name"
                                                name="first_name"
                                                defaultValue={editingResident.first_name}
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_middle_name">Middle Name</Label>
                                            <Input
                                                id="edit_middle_name"
                                                name="middle_name"
                                                defaultValue={editingResident.middle_name || ''}
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_last_name">Last Name</Label>
                                            <Input
                                                id="edit_last_name"
                                                name="last_name"
                                                defaultValue={editingResident.last_name}
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_email">Email</Label>
                                            <Input
                                                id="edit_email"
                                                name="email"
                                                type="email"
                                                defaultValue={editingResident.email}
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_contact_number">Contact Number</Label>
                                            <Input
                                                id="edit_contact_number"
                                                name="contact_number"
                                                defaultValue={editingResident.contact_number || ''}
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_course">Course</Label>
                                            <Input
                                                id="edit_course"
                                                name="course"
                                                defaultValue={editingResident.course}
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_year_level">Year Level</Label>
                                            <select
                                                id="edit_year_level"
                                                name="year_level"
                                                defaultValue={editingResident.year_level}
                                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                required
                                            >
                                                {yearLevels.map((level) => (
                                                    <option key={level} value={level}>
                                                        {level}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_status">Status</Label>
                                            <select
                                                id="edit_status"
                                                name="status"
                                                defaultValue={editingResident.status}
                                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                required
                                            >
                                                {statuses.map((status) => (
                                                    <option key={status} value={status}>
                                                        {status.charAt(0).toUpperCase() + status.slice(1)}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </TabsContent>

                                    {/* Account Tab */}
                                    <TabsContent value="account" className="space-y-6">
                                        {Object.keys(errors).length > 0 && (
                                            <AlertError errors={Object.values(errors)} />
                                        )}

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_password">New Password</Label>
                                            <Input
                                                id="edit_password"
                                                name="password"
                                                type="password"
                                                placeholder="Leave blank to keep current password"
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_password_confirmation">Confirm Password</Label>
                                            <Input
                                                id="edit_password_confirmation"
                                                name="password_confirmation"
                                                type="password"
                                                placeholder="Confirm new password"
                                            />
                                        </div>
                                    </TabsContent>
                                </Tabs>

                                <div className="flex justify-end gap-3 pt-6 border-t mt-6">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setEditingResident(null)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button type="submit">Save Changes</Button>
                                </div>
                            </form>
                        </div>
                    )}
                </SheetContent>
            </Sheet>
        </AppLayout>
    );
}

