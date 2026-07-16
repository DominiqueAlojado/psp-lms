import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import { ExportButton } from '@/components/export-button';
import HeadingSmall from '@/components/heading-small';
import { CreateInstitutionSheet } from '@/components/institutions/create-institution-sheet';
import { EditInstitutionSheet } from '@/components/institutions/edit-institution-sheet';
import { InstitutionLogsSheet } from '@/components/institutions/institution-logs-sheet';
import { InstitutionTable } from '@/components/institutions/institution-table';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/hooks/use-permissions';
import { preserveOrgParam } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Building2, Plus, Search } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Institutions',
        href: '/institutions',
    },
];

interface TrainingOfficer {
    name: string;
    email: string;
}

interface Institution {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: string;
    is_active: boolean;
    residents_count: number;
    users_count: number;
    training_officers_count: number;
    training_officers?: TrainingOfficer[];
    updated_at: string;
}

interface PaginatedInstitutions {
    data: Institution[];
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
    institutions: PaginatedInstitutions;
    filters: {
        search?: string;
        type?: string;
        status?: string;
    };
    types: string[];
    typeStats: Record<string, number>;
}

export default function InstitutionsIndex({
    institutions,
    filters,
    types,
    typeStats,
}: Props) {
    const { hasPermission } = usePermissions();
    const [search, setSearch] = useState(filters.search || '');
    const [localFilters, setLocalFilters] = useState(filters);
    const [editingInstitution, setEditingInstitution] =
        useState<Institution | null>(null);
    const [viewingLogsInstitution, setViewingLogsInstitution] =
        useState<Institution | null>(null);
    const [addingInstitution, setAddingInstitution] = useState(false);
    const [deletingInstitution, setDeletingInstitution] =
        useState<Institution | null>(null);
    const page = usePage<{
        auth: { currentOrganization?: { slug?: string | null } };
    }>();
    const currentOrgSlug = page.props.auth.currentOrganization?.slug;

    const applyFilters = useCallback((newFilters: typeof filters) => {
        router.get(preserveOrgParam('/institutions', currentOrgSlug), newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    }, [currentOrgSlug]);

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
            preserveOrgParam('/institutions', currentOrgSlug),
            {},
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const confirmDelete = () => {
        if (!deletingInstitution) return;
        router.delete(
            preserveOrgParam(`/institutions/${deletingInstitution.id}`, currentOrgSlug),
            {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Institution deleted successfully');
            },
            onError: (errors) => {
                if (errors.error) {
                    toast.error(errors.error as string);
                } else {
                    toast.error('Failed to delete institution');
                }
            },
            onFinish: () => setDeletingInstitution(null),
            },
        );
    };

    const hasActiveFilters = search || localFilters.type || localFilters.status;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Institutions" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <HeadingSmall
                        title="Institutions"
                        description="Manage and organize all institutions in the system"
                    />
                    <div className="flex gap-2">
                        <ExportButton
                            exportUrl="/institutions/export"
                            filters={filters}
                            successMessage="Exporting institutions..."
                            disabled={!hasPermission('export-institutions')}
                        />
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <span className="inline-block">
                                        <Button
                                            onClick={() =>
                                                setAddingInstitution(true)
                                            }
                                            disabled={
                                                !hasPermission(
                                                    'create-institutions',
                                                )
                                            }
                                        >
                                            <Plus className="mr-2 h-4 w-4" />
                                            Add Institution
                                        </Button>
                                    </span>
                                </TooltipTrigger>
                                {!hasPermission('create-institutions') && (
                                    <TooltipContent>
                                        <p>
                                            You don't have permission to create
                                            institutions
                                        </p>
                                    </TooltipContent>
                                )}
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>

                {/* Statistics Cards */}
                {types.length > 0 && (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {types.map((type) => {
                            const count = typeStats[type] || 0;
                            const isFiltered = filters.type === type;

                            return (
                                <StatCard
                                    key={type}
                                    title={
                                        type.charAt(0).toUpperCase() +
                                        type.slice(1)
                                    }
                                    value={count}
                                    description={`${count === 1 ? 'institution' : 'institutions'}`}
                                    icon={Building2}
                                    iconColor={
                                        isFiltered
                                            ? 'text-primary'
                                            : 'text-muted-foreground'
                                    }
                                    className={
                                        isFiltered ? 'border-primary' : ''
                                    }
                                    onClick={() => {
                                        if (isFiltered) {
                                            updateFilter('type', undefined);
                                        } else {
                                            updateFilter('type', type);
                                        }
                                    }}
                                />
                            );
                        })}
                    </div>
                )}

                {/* Search and Filters */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Search institutions..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pl-9"
                        />
                    </div>
                    <div className="flex gap-2">
                        <select
                            value={localFilters.status || ''}
                            onChange={(e) =>
                                updateFilter(
                                    'status',
                                    e.target.value || undefined,
                                )
                            }
                            className="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        {hasActiveFilters && (
                            <Button variant="ghost" onClick={clearFilters}>
                                Clear Filters
                            </Button>
                        )}
                    </div>
                </div>

                {/* Table */}
                <InstitutionTable
                    institutions={institutions}
                    filters={filters}
                    currentOrgSlug={currentOrgSlug}
                    onEdit={setEditingInstitution}
                    onDelete={setDeletingInstitution}
                    onViewLogs={(institution) =>
                        setViewingLogsInstitution(institution)
                    }
                />
            </div>

            {/* Edit Sheet */}
            <EditInstitutionSheet
                open={!!editingInstitution}
                institution={editingInstitution}
                onClose={() => setEditingInstitution(null)}
            />

            {/* Create Sheet */}
            <CreateInstitutionSheet
                open={addingInstitution}
                onClose={() => setAddingInstitution(false)}
            />

            {/* Delete Dialog */}
            <DeleteConfirmationDialog
                open={!!deletingInstitution}
                title="Delete Institution?"
                itemName={deletingInstitution?.name}
                description={
                    deletingInstitution?.description
                        ? `${deletingInstitution.description.substring(0, 100)}${deletingInstitution.description.length > 100 ? '...' : ''}`
                        : undefined
                }
                warningMessage="This action cannot be undone. This will permanently delete this institution and all associated data."
                confirmText="Delete Institution"
                onConfirm={confirmDelete}
                onCancel={() => setDeletingInstitution(null)}
            />

            {/* Institution Logs Sheet */}
            <InstitutionLogsSheet
                institution={viewingLogsInstitution}
                currentOrgSlug={currentOrgSlug}
                open={!!viewingLogsInstitution}
                onOpenChange={(open) =>
                    !open && setViewingLogsInstitution(null)
                }
            />
        </AppLayout>
    );
}
