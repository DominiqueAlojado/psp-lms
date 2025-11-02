import AlertError from '@/components/alert-error';
import HeadingSmall from '@/components/heading-small';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Edit, Eye, Filter, Plus, Search, Trash2, X } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { z } from 'zod';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Residents',
        href: '/residents',
    },
];

// Zod Validation Schemas
const philippinePhoneRegex = /^(\+63|0)?9\d{9}$/;

const step1Schema = z.object({
    organization_id: z.string().min(1, 'Organization is required'),
    first_name: z.string().min(1, 'First name is required').max(255),
    middle_name: z.string().max(255).optional().or(z.literal('')),
    last_name: z.string().min(1, 'Last name is required').max(255),
    email: z.string().email('Please enter a valid email address'),
    contact_number: z
        .string()
        .min(1, 'Contact number is required')
        .regex(
            philippinePhoneRegex,
            'Contact number must be a valid Philippine mobile number (e.g., 09123456789 or +639123456789)',
        ),
    course: z.string().min(1, 'Course is required').max(255),
    year_level: z.string().min(1, 'Year level is required'),
    status: z.enum(['active', 'inactive']),
});

const step2Schema = z
    .object({
        password: z.string().min(8, 'Password must be at least 8 characters'),
        password_confirmation: z
            .string()
            .min(1, 'Please confirm your password'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: "Passwords don't match",
        path: ['password_confirmation'],
    });

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

export default function ResidentsIndex({
    residents,
    organizations,
    filters,
    yearLevels,
    statuses,
    courses,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [showFilters, setShowFilters] = useState(false);
    const [localFilters, setLocalFilters] = useState(filters);
    const [editingResident, setEditingResident] = useState<Resident | null>(
        null,
    );
    const [addingResident, setAddingResident] = useState(false);
    const [currentStep, setCurrentStep] = useState(1);
    const [formData, setFormData] = useState<Record<string, string>>({});
    const [validationErrors, setValidationErrors] = useState<
        Record<string, string>
    >({});
    const [deletingResident, setDeletingResident] = useState<{
        id: number;
        name: string;
    } | null>(null);
    const { errors } = usePage<{ errors: Record<string, string> }>().props;

    const applyFilters = useCallback((newFilters: typeof filters) => {
        router.get('/residents', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    }, []);

    // Debounced search
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (search !== filters.search) {
                applyFilters({ ...localFilters, search });
            }
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [search, filters.search, localFilters, applyFilters]);

    const updateFilter = (key: string, value: string | undefined) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        applyFilters(newFilters);
    };

    const clearFilters = () => {
        setSearch('');
        setLocalFilters({});
        router.get(
            '/residents',
            {},
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const hasActiveFilters = Object.keys(filters).some(
        (key) => filters[key as keyof typeof filters],
    );

    const confirmDelete = () => {
        if (!deletingResident) return;
        router.delete(`/residents/${deletingResident.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Resident deleted successfully');
            },
            onError: () => {
                toast.error('Failed to delete resident');
            },
            onFinish: () => setDeletingResident(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Residents" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <HeadingSmall
                        title="Residents"
                        description="Search and manage all residents across organizations"
                    />
                    <Button onClick={() => setAddingResident(true)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Resident
                    </Button>
                </div>

                {/* Search and Filter Bar */}
                <Card>
                    <CardContent className="space-y-6 p-8">
                        <div className="flex gap-4">
                            {/* Search Input */}
                            <div className="relative flex-1">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
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
                                        {
                                            Object.keys(filters).filter(
                                                (k) =>
                                                    filters[
                                                        k as keyof typeof filters
                                                    ],
                                            ).length
                                        }
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
                            <div className="grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2 lg:grid-cols-4">
                                {/* Organization Filter */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Organization
                                    </label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                        value={
                                            localFilters.organization_id || ''
                                        }
                                        onChange={(e) =>
                                            updateFilter(
                                                'organization_id',
                                                e.target.value || undefined,
                                            )
                                        }
                                    >
                                        <option value="">
                                            All Organizations
                                        </option>
                                        {organizations.map((org) => (
                                            <option key={org.id} value={org.id}>
                                                {org.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Year Level Filter */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Year Level
                                    </label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                        value={localFilters.year_level || ''}
                                        onChange={(e) =>
                                            updateFilter(
                                                'year_level',
                                                e.target.value || undefined,
                                            )
                                        }
                                    >
                                        <option value="">
                                            All Year Levels
                                        </option>
                                        {yearLevels.map((level) => (
                                            <option key={level} value={level}>
                                                {level}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Status Filter */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Status
                                    </label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                        value={localFilters.status || ''}
                                        onChange={(e) =>
                                            updateFilter(
                                                'status',
                                                e.target.value || undefined,
                                            )
                                        }
                                    >
                                        <option value="">All Statuses</option>
                                        {statuses.map((status) => (
                                            <option key={status} value={status}>
                                                {status
                                                    .charAt(0)
                                                    .toUpperCase() +
                                                    status.slice(1)}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Course Filter */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        Course
                                    </label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                        value={localFilters.course || ''}
                                        onChange={(e) =>
                                            updateFilter(
                                                'course',
                                                e.target.value || undefined,
                                            )
                                        }
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
                                    <Badge
                                        variant="secondary"
                                        className="gap-1"
                                    >
                                        Search: {filters.search}
                                        <X
                                            className="h-3 w-3 cursor-pointer"
                                            onClick={() => {
                                                setSearch('');
                                                updateFilter(
                                                    'search',
                                                    undefined,
                                                );
                                            }}
                                        />
                                    </Badge>
                                )}
                                {filters.organization_id && (
                                    <Badge
                                        variant="secondary"
                                        className="gap-1"
                                    >
                                        Org:{' '}
                                        {
                                            organizations.find(
                                                (o) =>
                                                    o.id ===
                                                    Number(
                                                        filters.organization_id,
                                                    ),
                                            )?.name
                                        }
                                        <X
                                            className="h-3 w-3 cursor-pointer"
                                            onClick={() =>
                                                updateFilter(
                                                    'organization_id',
                                                    undefined,
                                                )
                                            }
                                        />
                                    </Badge>
                                )}
                                {filters.year_level && (
                                    <Badge
                                        variant="secondary"
                                        className="gap-1"
                                    >
                                        Year: {filters.year_level}
                                        <X
                                            className="h-3 w-3 cursor-pointer"
                                            onClick={() =>
                                                updateFilter(
                                                    'year_level',
                                                    undefined,
                                                )
                                            }
                                        />
                                    </Badge>
                                )}
                                {filters.status && (
                                    <Badge
                                        variant="secondary"
                                        className="gap-1"
                                    >
                                        Status: {filters.status}
                                        <X
                                            className="h-3 w-3 cursor-pointer"
                                            onClick={() =>
                                                updateFilter(
                                                    'status',
                                                    undefined,
                                                )
                                            }
                                        />
                                    </Badge>
                                )}
                                {filters.course && (
                                    <Badge
                                        variant="secondary"
                                        className="gap-1"
                                    >
                                        Course: {filters.course}
                                        <X
                                            className="h-3 w-3 cursor-pointer"
                                            onClick={() =>
                                                updateFilter(
                                                    'course',
                                                    undefined,
                                                )
                                            }
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
                                    Showing {residents.from || 0} to{' '}
                                    {residents.to || 0} of {residents.total}{' '}
                                    residents
                                </p>
                            </div>

                            {/* Table */}
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="py-4">
                                            Name
                                        </TableHead>
                                        <TableHead className="py-4">
                                            Email
                                        </TableHead>
                                        <TableHead className="py-4">
                                            Organization
                                        </TableHead>
                                        <TableHead className="py-4">
                                            Year Level
                                        </TableHead>
                                        <TableHead className="py-4">
                                            Status
                                        </TableHead>
                                        <TableHead className="w-[100px] py-4">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {residents.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="py-8 text-center text-muted-foreground"
                                            >
                                                No residents found. Try
                                                adjusting your filters.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        residents.data.map((resident) => (
                                            <TableRow key={resident.id}>
                                                <TableCell className="py-4 font-medium">
                                                    {
                                                        resident.full_name_with_middle_initial
                                                    }
                                                </TableCell>
                                                <TableCell className="py-4">
                                                    {resident.email}
                                                </TableCell>
                                                <TableCell className="py-4">
                                                    <span className="text-sm text-muted-foreground">
                                                        {
                                                            resident
                                                                .organization
                                                                .name
                                                        }
                                                    </span>
                                                </TableCell>
                                                <TableCell className="py-4">
                                                    {resident.year_level}
                                                </TableCell>
                                                <TableCell className="py-4">
                                                    <Badge
                                                        variant={
                                                            resident.status ===
                                                            'active'
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {resident.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="py-4">
                                                    <div className="flex gap-2">
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
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                setDeletingResident(
                                                                    {
                                                                        id: resident.id,
                                                                        name: resident.full_name,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="h-4 w-4 text-destructive" />
                                                        </Button>
                                                        <Link
                                                            href={`/residents/${resident.id}`}
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                            >
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
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => {
                                                if (link.url) {
                                                    const url = new URL(
                                                        link.url,
                                                    );
                                                    const page =
                                                        url.searchParams.get(
                                                            'page',
                                                        );
                                                    router.get(
                                                        '/residents',
                                                        { ...filters, page },
                                                        {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Edit Resident Sheet */}
            <Sheet
                open={!!editingResident}
                onOpenChange={(open) => !open && setEditingResident(null)}
            >
                <SheetContent className="overflow-y-auto p-0 sm:max-w-[600px]">
                    {editingResident && (
                        <div className="p-8">
                            <SheetHeader className="pb-6">
                                <SheetTitle>Edit Resident</SheetTitle>
                                <SheetDescription>
                                    Update resident information for{' '}
                                    {editingResident.full_name}
                                </SheetDescription>
                            </SheetHeader>

                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    const formData = new FormData(
                                        e.currentTarget,
                                    );
                                    router.patch(
                                        `/residents/${editingResident.id}`,
                                        Object.fromEntries(formData),
                                        {
                                            preserveScroll: true,
                                            preserveState: true,
                                            onSuccess: () => {
                                                toast.success(
                                                    'Resident updated successfully',
                                                );
                                                setEditingResident(null);
                                            },
                                            onError: () => {
                                                toast.error(
                                                    'Failed to update resident',
                                                );
                                            },
                                        },
                                    );
                                }}
                            >
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

                                    {/* Personal Data Tab */}
                                    <TabsContent
                                        value="personal"
                                        className="space-y-6"
                                    >
                                        {Object.keys(errors).length > 0 && (
                                            <AlertError
                                                errors={Object.values(errors)}
                                            />
                                        )}

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
                                                required
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
                                                required
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
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_contact_number">
                                                Contact Number
                                            </Label>
                                            <Input
                                                id="edit_contact_number"
                                                name="contact_number"
                                                type="tel"
                                                pattern="(\+63|0)?9\d{9}"
                                                placeholder="09123456789 or +639123456789"
                                                defaultValue={
                                                    editingResident.contact_number ||
                                                    ''
                                                }
                                                onInput={(e) => {
                                                    const input =
                                                        e.currentTarget;
                                                    // Only allow numbers, plus sign, and leading zero
                                                    input.value =
                                                        input.value.replace(
                                                            /[^\d+]/g,
                                                            '',
                                                        );
                                                }}
                                                required
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                Philippine mobile number format
                                                (11 digits)
                                            </p>
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
                                                required
                                            />
                                        </div>

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
                                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                                required
                                            >
                                                {yearLevels.map((level) => (
                                                    <option
                                                        key={level}
                                                        value={level}
                                                    >
                                                        {level}
                                                    </option>
                                                ))}
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
                                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                                required
                                            >
                                                {statuses.map((status) => (
                                                    <option
                                                        key={status}
                                                        value={status}
                                                    >
                                                        {status
                                                            .charAt(0)
                                                            .toUpperCase() +
                                                            status.slice(1)}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </TabsContent>

                                    {/* Account Tab */}
                                    <TabsContent
                                        value="account"
                                        className="space-y-6"
                                    >
                                        {Object.keys(errors).length > 0 && (
                                            <AlertError
                                                errors={Object.values(errors)}
                                            />
                                        )}

                                        <div className="space-y-2">
                                            <Label htmlFor="edit_password">
                                                New Password
                                            </Label>
                                            <Input
                                                id="edit_password"
                                                name="password"
                                                type="password"
                                                placeholder="Leave blank to keep current password"
                                            />
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
                                            />
                                        </div>
                                    </TabsContent>
                                </Tabs>

                                <div className="mt-6 flex justify-end gap-3 border-t pt-6">
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

            {/* Add Resident Sheet */}
            <Sheet
                open={addingResident}
                onOpenChange={(open) => {
                    setAddingResident(open);
                    if (!open) {
                        setCurrentStep(1);
                        setFormData({});
                        setValidationErrors({});
                    }
                }}
            >
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
                                className={`flex h-8 w-8 items-center justify-center rounded-full ${currentStep === 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'}`}
                            >
                                1
                            </div>
                            <div className="h-[2px] flex-1 bg-muted" />
                            <div
                                className={`flex h-8 w-8 items-center justify-center rounded-full ${currentStep === 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'}`}
                            >
                                2
                            </div>
                        </div>

                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                console.log(
                                    'Form submitted, current step:',
                                    currentStep,
                                );

                                const currentFormData = new FormData(
                                    e.currentTarget,
                                );
                                const currentData =
                                    Object.fromEntries(currentFormData);

                                console.log('Current form data:', currentData);

                                if (currentStep === 1) {
                                    // Validate step 1 data
                                    try {
                                        step1Schema.parse(currentData);
                                        setValidationErrors({});
                                        // Save step 1 data and move to step 2
                                        const dataToSave: Record<
                                            string,
                                            string
                                        > = {};
                                        Object.entries(currentData).forEach(
                                            ([key, value]) => {
                                                dataToSave[key] =
                                                    typeof value === 'string'
                                                        ? value
                                                        : '';
                                            },
                                        );
                                        setFormData((prev) => ({
                                            ...prev,
                                            ...dataToSave,
                                        }));
                                        setCurrentStep(2);
                                    } catch (error) {
                                        if (error instanceof z.ZodError) {
                                            const errors: Record<
                                                string,
                                                string
                                            > = {};
                                            error.issues.forEach((err) => {
                                                if (err.path[0]) {
                                                    errors[
                                                        err.path[0].toString()
                                                    ] = err.message;
                                                }
                                            });
                                            setValidationErrors(errors);
                                        }
                                    }
                                    return;
                                }

                                // Step 2: Validate and submit all data
                                const dataToSave: Record<string, string> = {};
                                Object.entries(currentData).forEach(
                                    ([key, value]) => {
                                        dataToSave[key] =
                                            typeof value === 'string'
                                                ? value
                                                : '';
                                    },
                                );
                                const allData = { ...formData, ...dataToSave };

                                console.log(
                                    'All form data being submitted:',
                                    allData,
                                );

                                try {
                                    step2Schema.parse(currentData);
                                    setValidationErrors({});

                                    console.log(
                                        'Validation passed, submitting to server...',
                                    );

                                    router.post('/residents', allData, {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            toast.success(
                                                'Resident created successfully',
                                            );
                                            setAddingResident(false);
                                            setCurrentStep(1);
                                            setFormData({});
                                            setValidationErrors({});
                                        },
                                        onError: () => {
                                            toast.error(
                                                'Failed to create resident. Please check the form.',
                                            );
                                        },
                                    });
                                } catch (error) {
                                    if (error instanceof z.ZodError) {
                                        console.error(
                                            'Step 2 Validation failed:',
                                            error.issues,
                                        );
                                        const errors: Record<string, string> =
                                            {};
                                        error.issues.forEach((err) => {
                                            if (err.path[0]) {
                                                errors[err.path[0].toString()] =
                                                    err.message;
                                            }
                                        });
                                        setValidationErrors(errors);
                                    }
                                }
                            }}
                        >
                            {/* Step 1: Personal Data */}
                            {currentStep === 1 && (
                                <div className="space-y-6">
                                    <h3 className="text-lg font-semibold">
                                        Step 1: Personal Information
                                    </h3>

                                    {(Object.keys(errors).length > 0 ||
                                        Object.keys(validationErrors).length >
                                            0) && (
                                        <AlertError
                                            errors={
                                                [
                                                    ...Object.values(errors),
                                                    ...Object.values(
                                                        validationErrors,
                                                    ),
                                                ] as string[]
                                            }
                                        />
                                    )}

                                    <div className="space-y-2">
                                        <Label htmlFor="add_organization_id">
                                            Organization
                                        </Label>
                                        <select
                                            id="add_organization_id"
                                            name="organization_id"
                                            defaultValue={
                                                formData.organization_id || ''
                                            }
                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                            required
                                        >
                                            <option value="">
                                                Select organization...
                                            </option>
                                            {organizations.map((org) => (
                                                <option
                                                    key={org.id}
                                                    value={org.id}
                                                >
                                                    {org.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="add_first_name">
                                            First Name
                                        </Label>
                                        <Input
                                            id="add_first_name"
                                            name="first_name"
                                            defaultValue={
                                                formData.first_name || ''
                                            }
                                            required
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="add_middle_name">
                                            Middle Name
                                        </Label>
                                        <Input
                                            id="add_middle_name"
                                            name="middle_name"
                                            defaultValue={
                                                formData.middle_name || ''
                                            }
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="add_last_name">
                                            Last Name
                                        </Label>
                                        <Input
                                            id="add_last_name"
                                            name="last_name"
                                            defaultValue={
                                                formData.last_name || ''
                                            }
                                            required
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="add_email">Email</Label>
                                        <Input
                                            id="add_email"
                                            name="email"
                                            type="email"
                                            defaultValue={formData.email || ''}
                                            required
                                        />
                                        {validationErrors.email && (
                                            <p className="text-sm text-destructive">
                                                {validationErrors.email}
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="add_contact_number">
                                            Contact Number
                                        </Label>
                                        <Input
                                            id="add_contact_number"
                                            name="contact_number"
                                            type="tel"
                                            pattern="(\+63|0)?9\d{9}"
                                            placeholder="09123456789 or +639123456789"
                                            defaultValue={
                                                formData.contact_number || ''
                                            }
                                            onInput={(e) => {
                                                const input = e.currentTarget;
                                                // Only allow numbers, plus sign, and leading zero
                                                input.value =
                                                    input.value.replace(
                                                        /[^\d+]/g,
                                                        '',
                                                    );
                                            }}
                                            required
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Philippine mobile number format (11
                                            digits)
                                        </p>
                                        {validationErrors.contact_number && (
                                            <p className="text-sm text-destructive">
                                                {
                                                    validationErrors.contact_number
                                                }
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="add_course">
                                            Course
                                        </Label>
                                        <Input
                                            id="add_course"
                                            name="course"
                                            defaultValue={formData.course || ''}
                                            required
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="add_year_level">
                                            Year Level
                                        </Label>
                                        <select
                                            id="add_year_level"
                                            name="year_level"
                                            defaultValue={
                                                formData.year_level || ''
                                            }
                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                            required
                                        >
                                            <option value="">
                                                Select year level...
                                            </option>
                                            {yearLevels.map((level) => (
                                                <option
                                                    key={level}
                                                    value={level}
                                                >
                                                    {level}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="add_status">
                                            Status
                                        </Label>
                                        <select
                                            id="add_status"
                                            name="status"
                                            defaultValue={
                                                formData.status || 'active'
                                            }
                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                            required
                                        >
                                            {statuses.map((status) => (
                                                <option
                                                    key={status}
                                                    value={status}
                                                >
                                                    {status
                                                        .charAt(0)
                                                        .toUpperCase() +
                                                        status.slice(1)}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            )}

                            {/* Step 2: Account */}
                            {currentStep === 2 && (
                                <div className="space-y-6">
                                    <h3 className="text-lg font-semibold">
                                        Step 2: Account Information
                                    </h3>
                                    {(Object.keys(errors).length > 0 ||
                                        Object.keys(validationErrors).length >
                                            0) && (
                                        <AlertError
                                            errors={
                                                [
                                                    ...Object.values(errors),
                                                    ...Object.values(
                                                        validationErrors,
                                                    ),
                                                ] as string[]
                                            }
                                        />
                                    )}

                                    <div className="space-y-2">
                                        <Label htmlFor="add_password">
                                            Password
                                        </Label>
                                        <Input
                                            id="add_password"
                                            name="password"
                                            type="password"
                                            defaultValue={
                                                formData.password || ''
                                            }
                                            required
                                        />
                                        {validationErrors.password && (
                                            <p className="text-sm text-destructive">
                                                {validationErrors.password}
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="add_password_confirmation">
                                            Confirm Password
                                        </Label>
                                        <Input
                                            id="add_password_confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            defaultValue={
                                                formData.password_confirmation ||
                                                ''
                                            }
                                            required
                                        />
                                        {validationErrors.password_confirmation && (
                                            <p className="text-sm text-destructive">
                                                {
                                                    validationErrors.password_confirmation
                                                }
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Navigation Buttons */}
                            <div className="mt-6 flex justify-between gap-3 border-t pt-6">
                                {currentStep === 1 ? (
                                    <>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => {
                                                setAddingResident(false);
                                                setCurrentStep(1);
                                            }}
                                        >
                                            Cancel
                                        </Button>
                                        <Button type="submit">Next</Button>
                                    </>
                                ) : (
                                    <>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={(e) => {
                                                // Save current step 2 data before going back
                                                const form =
                                                    e.currentTarget.closest(
                                                        'form',
                                                    );
                                                if (form) {
                                                    const currentFormData =
                                                        new FormData(form);
                                                    const currentData =
                                                        Object.fromEntries(
                                                            currentFormData,
                                                        );
                                                    const dataToSave: Record<
                                                        string,
                                                        string
                                                    > = {};
                                                    Object.entries(
                                                        currentData,
                                                    ).forEach(
                                                        ([key, value]) => {
                                                            dataToSave[key] =
                                                                typeof value ===
                                                                'string'
                                                                    ? value
                                                                    : '';
                                                        },
                                                    );
                                                    setFormData((prev) => ({
                                                        ...prev,
                                                        ...dataToSave,
                                                    }));
                                                }
                                                setValidationErrors({});
                                                setCurrentStep(1);
                                            }}
                                        >
                                            Back
                                        </Button>
                                        <Button type="submit">
                                            Create Resident
                                        </Button>
                                    </>
                                )}
                            </div>
                        </form>
                    </div>
                </SheetContent>
            </Sheet>

            {/* Delete Confirmation Dialog */}
            <AlertDialog
                open={!!deletingResident}
                onOpenChange={(open) => !open && setDeletingResident(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will permanently delete the resident "
                            {deletingResident?.name}". This action cannot be
                            undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmDelete}
                            className="bg-destructive text-white hover:bg-destructive/90"
                        >
                            <Trash2 className="h-4" />
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
