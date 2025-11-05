import { ExportButton } from '@/components/export-button';
import HeadingSmall from '@/components/heading-small';
import { CreateStaffSheet } from '@/components/staff/create-staff-sheet';
import { EditStaffSheet } from '@/components/staff/edit-staff-sheet';
import { StaffTable } from '@/components/staff/staff-table';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Search, UserCog } from 'lucide-react';
import { useCallback, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Staff',
        href: '/staff',
    },
];

interface Role {
    id: number;
    name: string;
}

interface Organization {
    id: number;
    name: string;
}

interface Staff {
    id: number;
    uuid: string;
    name: string;
    email: string;
    roles: string[];
    primary_role: string;
    current_organization: string;
    organizations_count: number;
    created_at: string;
    updated_at: string;
}

interface PaginatedStaff {
    data: Staff[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface RoleStat {
    role: string;
    count: number;
}

interface PageProps {
    staff: PaginatedStaff;
    filters: {
        search?: string;
        role?: string;
        organization?: string;
        sort?: string;
        direction?: string;
    };
    roleStats: RoleStat[];
    roles: Role[];
    organizations: Organization[];
}

export default function StaffIndex() {
    const { staff, filters, roleStats, roles, organizations } =
        usePage<PageProps>().props;
    const { hasPermission } = usePermissions();

    const [createOpen, setCreateOpen] = useState(false);
    const [editingStaff, setEditingStaff] = useState<Staff | null>(null);
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [roleFilter, setRoleFilter] = useState(filters.role || '');
    const [orgFilter, setOrgFilter] = useState(filters.organization || '');

    const handleSearch = useCallback(() => {
        router.get(
            '/staff',
            {
                search: searchQuery || undefined,
                role: roleFilter || undefined,
                organization: orgFilter || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    }, [searchQuery, roleFilter, orgFilter]);

    const handleClearFilters = () => {
        setSearchQuery('');
        setRoleFilter('');
        setOrgFilter('');
        router.get('/staff', {}, { preserveState: true, preserveScroll: true });
    };

    const hasActiveFilters = searchQuery || roleFilter || orgFilter;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Staff Management" />

            <div className="space-y-8 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-2">
                        <HeadingSmall icon={UserCog}>
                            Staff Management
                        </HeadingSmall>
                        <p className="text-sm text-muted-foreground">
                            Manage administrative staff, training officers, and
                            system administrators
                        </p>
                    </div>
                    <Button
                        onClick={() => setCreateOpen(true)}
                        disabled={!hasPermission('create-staff')}
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        Add Staff Member
                    </Button>
                </div>

                {/* Statistics */}
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Total Staff"
                        value={staff.total}
                        icon={UserCog}
                    />
                    {roleStats.slice(0, 3).map((stat) => (
                        <StatCard
                            key={stat.role}
                            title={stat.role}
                            value={stat.count}
                            icon={UserCog}
                        />
                    ))}
                </div>

                {/* Filters and Actions */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-1 gap-3">
                        <div className="relative flex-1">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Search by name or email..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        handleSearch();
                                    }
                                }}
                                className="pl-9"
                            />
                        </div>
                        <select
                            value={roleFilter}
                            onChange={(e) => setRoleFilter(e.target.value)}
                            className="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">All Roles</option>
                            {roles.map((role) => (
                                <option key={role.id} value={role.name}>
                                    {role.name}
                                </option>
                            ))}
                        </select>
                        <select
                            value={orgFilter}
                            onChange={(e) => setOrgFilter(e.target.value)}
                            className="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">All Organizations</option>
                            {organizations.map((org) => (
                                <option key={org.id} value={org.id}>
                                    {org.name}
                                </option>
                            ))}
                        </select>
                        <Button onClick={handleSearch}>Search</Button>
                        {hasActiveFilters && (
                            <Button
                                variant="outline"
                                onClick={handleClearFilters}
                            >
                                Clear
                            </Button>
                        )}
                    </div>
                    <ExportButton
                        href="/staff/export"
                        filters={{
                            search: searchQuery,
                            role: roleFilter,
                            organization: orgFilter,
                        }}
                        disabled={!hasPermission('export-staff')}
                    />
                </div>

                {/* Staff Table */}
                <div className="rounded-lg">
                    <StaffTable
                        staff={staff.data}
                        onEdit={(staffMember) => setEditingStaff(staffMember)}
                    />
                </div>

                {/* Pagination Info */}
                {staff.total > 0 && (
                    <div className="text-sm text-muted-foreground">
                        Showing {(staff.current_page - 1) * staff.per_page + 1}{' '}
                        to{' '}
                        {Math.min(
                            staff.current_page * staff.per_page,
                            staff.total,
                        )}{' '}
                        of {staff.total} staff members
                    </div>
                )}
            </div>

            {/* Create Staff Sheet */}
            <CreateStaffSheet
                open={createOpen}
                onOpenChange={setCreateOpen}
                roles={roles}
                organizations={organizations}
            />

            {/* Edit Staff Sheet */}
            <EditStaffSheet
                staff={editingStaff}
                open={!!editingStaff}
                onOpenChange={(open) => !open && setEditingStaff(null)}
                roles={roles}
                organizations={organizations}
            />
        </AppLayout>
    );
}
