import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import { ExportButton } from '@/components/export-button';
import HeadingSmall from '@/components/heading-small';
import { CreateResidentSheet } from '@/components/residents/create-resident-sheet';
import { EditResidentSheet } from '@/components/residents/edit-resident-sheet';
import { ResidentFilters } from '@/components/residents/resident-filters';
import { ResidentLogsSheet } from '@/components/residents/resident-logs-sheet';
import { ResidentTable } from '@/components/residents/resident-table';
import { TransferResidentDialog } from '@/components/residents/transfer-resident-dialog';
import { ViewResidentSheet } from '@/components/residents/view-resident-sheet';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { GraduationCap, Plus, Users } from 'lucide-react';
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
    updated_at: string;
    organizations_count: number;
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
    yearLevelStats: Record<string, number>;
}

export default function ResidentsIndex({
    residents,
    organizations,
    filters,
    yearLevels,
    statuses,
    courses,
    yearLevelStats,
}: Props) {
    const { hasPermission } = usePermissions();
    const [search, setSearch] = useState(filters.search || '');
    const [showFilters, setShowFilters] = useState(false);
    const [localFilters, setLocalFilters] = useState(filters);
    const [viewingResident, setViewingResident] = useState<Resident | null>(
        null,
    );
    const [viewOrganizations, setViewOrganizations] = useState<{
        current: any[];
        available: any[];
        history: any[];
    }>({ current: [], available: [], history: [] });
    const [editingResident, setEditingResident] = useState<Resident | null>(
        null,
    );
    const [transferringResident, setTransferringResident] = useState<Resident | null>(
        null,
    );
    const [addingResident, setAddingResident] = useState(false);
    const [deletingResident, setDeletingResident] = useState<Resident | null>(
        null,
    );
    const [viewingLogsResident, setViewingLogsResident] = useState<Resident | null>(
        null,
    );
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

    const fetchResidentOrganizations = async (residentId: number) => {
        try {
            const response = await fetch(`/residents/${residentId}`);
            const data = await response.json();

            setViewOrganizations({
                current: data.currentOrganizations || [],
                available: data.availableOrganizations || [],
                history: data.organizationHistory || [],
            });
        } catch (error) {
            console.error('Error fetching organizations:', error);
            toast.error('Failed to load organization data');
        }
    };

    const handleViewResident = async (resident: Resident) => {
        setViewingResident(resident);
        await fetchResidentOrganizations(resident.id);
    };

    const handleRefreshOrganizations = () => {
        if (viewingResident) {
            fetchResidentOrganizations(viewingResident.id);
        }
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
                    <div className="flex gap-2">
                        <ExportButton
                            exportUrl="/residents/export"
                            filters={filters}
                            successMessage="Exporting residents..."
                            disabled={!hasPermission('export-residents')}
                        />
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <span className="inline-block">
                                        <Button
                                            onClick={() =>
                                                setAddingResident(true)
                                            }
                                            disabled={
                                                !hasPermission(
                                                    'create-residents',
                                                )
                                            }
                                        >
                                            <Plus className="mr-2 h-4 w-4" />
                                            Add Resident
                                        </Button>
                                    </span>
                                </TooltipTrigger>
                                {!hasPermission('create-residents') && (
                                    <TooltipContent>
                                        <p>
                                            You don't have permission to create
                                            residents
                                        </p>
                                    </TooltipContent>
                                )}
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    {yearLevels.map((level) => {
                        const count = yearLevelStats[level] || 0;
                        const isFiltered = filters.year_level === level;

                        return (
                            <StatCard
                                key={level}
                                title={level}
                                value={count}
                                description={`${count === 1 ? 'resident' : 'residents'}`}
                                icon={
                                    level === 'Graduate' ? GraduationCap : Users
                                }
                                iconColor={
                                    isFiltered
                                        ? 'text-primary'
                                        : 'text-muted-foreground'
                                }
                                className={isFiltered ? 'border-primary' : ''}
                                onClick={() => {
                                    if (isFiltered) {
                                        // Remove filter
                                        updateFilter('year_level', undefined);
                                    } else {
                                        // Apply filter
                                        updateFilter('year_level', level);
                                    }
                                }}
                            />
                        );
                    })}
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
                    onView={handleViewResident}
                    onEdit={setEditingResident}
                    onTransfer={setTransferringResident}
                    onDelete={setDeletingResident}
                    onViewLogs={setViewingLogsResident}
                />
            </div>

            {/* View Sheet */}
            <ViewResidentSheet
                open={!!viewingResident}
                resident={viewingResident}
                currentOrganizations={viewOrganizations.current}
                availableOrganizations={viewOrganizations.available}
                organizationHistory={viewOrganizations.history}
                onClose={() => {
                    setViewingResident(null);
                    setViewOrganizations({ current: [], available: [], history: [] });
                }}
                onRefresh={handleRefreshOrganizations}
            />

            {/* Edit Sheet */}
            <EditResidentSheet
                open={!!editingResident}
                resident={editingResident}
                organizations={organizations}
                yearLevels={yearLevels}
                statuses={statuses}
                onClose={() => setEditingResident(null)}
            />

            <TransferResidentDialog
                open={!!transferringResident}
                resident={transferringResident}
                organizations={organizations}
                onClose={() => setTransferringResident(null)}
            />

            {/* Create Sheet */}
            <CreateResidentSheet
                open={addingResident}
                organizations={organizations}
                yearLevels={yearLevels}
                statuses={statuses}
                onClose={() => setAddingResident(false)}
            />

            {/* Delete Dialog */}
            <DeleteConfirmationDialog
                open={!!deletingResident}
                title="Delete Resident?"
                itemName={
                    deletingResident
                        ? `${deletingResident.full_name} (${deletingResident.email})`
                        : undefined
                }
                description={
                    deletingResident
                        ? `${deletingResident.course} - ${deletingResident.year_level}`
                        : undefined
                }
                warningMessage="This action cannot be undone. This will permanently delete this resident from the system."
                confirmText="Delete Resident"
                onConfirm={confirmDelete}
                onCancel={() => setDeletingResident(null)}
            />

            {/* Resident Logs Sheet */}
            <ResidentLogsSheet
                resident={viewingLogsResident}
                open={!!viewingLogsResident}
                onOpenChange={(open) => {
                    if (!open) {
                        setViewingLogsResident(null);
                    }
                }}
            />
        </AppLayout>
    );
}
