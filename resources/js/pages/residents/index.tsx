import HeadingSmall from '@/components/heading-small';
import { CreateResidentSheet } from '@/components/residents/create-resident-sheet';
import { DeleteResidentDialog } from '@/components/residents/delete-resident-dialog';
import { EditResidentSheet } from '@/components/residents/edit-resident-sheet';
import { ResidentFilters } from '@/components/residents/resident-filters';
import { ResidentTable } from '@/components/residents/resident-table';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

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

                {/* Filters */}
                <ResidentFilters
                    search={search}
                    setSearch={setSearch}
                    showFilters={showFilters}
                    setShowFilters={setShowFilters}
                    filters={filters}
                    localFilters={localFilters}
                    updateFilter={updateFilter}
                    clearFilters={clearFilters}
                    organizations={organizations}
                    yearLevels={yearLevels}
                    statuses={statuses}
                    courses={courses}
                />

                {/* Table */}
                <ResidentTable
                    residents={residents}
                    filters={filters}
                    onEdit={setEditingResident}
                    onDelete={(id, name) => setDeletingResident({ id, name })}
                />
            </div>

            {/* Edit Sheet */}
            <EditResidentSheet
                open={!!editingResident}
                resident={editingResident}
                yearLevels={yearLevels}
                statuses={statuses}
                errors={errors}
                onClose={() => setEditingResident(null)}
            />

            {/* Create Sheet */}
            <CreateResidentSheet
                open={addingResident}
                organizations={organizations}
                yearLevels={yearLevels}
                statuses={statuses}
                errors={errors}
                onClose={() => setAddingResident(false)}
            />

            {/* Delete Dialog */}
            <DeleteResidentDialog
                open={!!deletingResident}
                residentName={deletingResident?.name || null}
                onConfirm={confirmDelete}
                onCancel={() => setDeletingResident(null)}
            />
        </AppLayout>
    );
}
