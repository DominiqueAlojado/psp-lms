import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/hooks/use-permissions';
import { router } from '@inertiajs/react';
import { Building2, Edit, Eye, Trash2 } from 'lucide-react';

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
    filters: Record<string, any>;
    onView: (resident: Resident) => void;
    onEdit: (resident: Resident) => void;
    onDelete: (resident: Resident) => void;
}

export function ResidentTable({ residents, filters, onView, onEdit, onDelete }: Props) {
    const { hasPermission } = usePermissions();

    return (
        <Card>
            <CardContent className="p-8">
                <div className="space-y-6">
                    {/* Results Count */}
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {residents.from || 0} to {residents.to || 0} of{' '}
                            {residents.total} residents
                        </p>
                    </div>

                    {/* Table */}
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="py-4">Name</TableHead>
                                <TableHead className="py-4">Email</TableHead>
                                <TableHead className="py-4">Home Institution</TableHead>
                                <TableHead className="py-4">Institutions</TableHead>
                                <TableHead className="py-4">Year Level</TableHead>
                                <TableHead className="py-4">Status</TableHead>
                                <TableHead className="py-4">Updated</TableHead>
                                <TableHead className="w-[100px] py-4">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {residents.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="py-8 text-center text-muted-foreground"
                                    >
                                        No residents found. Try adjusting your filters.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                residents.data.map((resident) => (
                                    <TableRow key={resident.id}>
                                        <TableCell className="py-4 font-medium">
                                            {resident.full_name_with_middle_initial}
                                        </TableCell>
                                        <TableCell className="py-4">
                                            {resident.email}
                                        </TableCell>
                                        <TableCell className="py-4">
                                            <span className="text-sm text-muted-foreground">
                                                {resident.organization.name}
                                            </span>
                                        </TableCell>
                                        <TableCell className="py-4">
                                            <div className="flex items-center gap-1">
                                                <Building2 className="h-4 w-4 text-muted-foreground" />
                                                <span className="text-sm">
                                                    {resident.organizations_count}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="py-4">
                                            {resident.year_level}
                                        </TableCell>
                                        <TableCell className="py-4">
                                            <Badge
                                                variant={
                                                    resident.status === 'active'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {resident.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="py-4">
                                            <span className="text-sm text-muted-foreground">
                                                {resident.updated_at}
                                            </span>
                                        </TableCell>
                                        <TableCell className="py-4">
                                            <div className="flex gap-2">
                                                <TooltipProvider>
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <span className="inline-block">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => onView(resident)}
                                                                    disabled={!hasPermission('view-residents')}
                                                                >
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </span>
                                                        </TooltipTrigger>
                                                        {!hasPermission('view-residents') && (
                                                            <TooltipContent>
                                                                <p>
                                                                    You don't have permission to view
                                                                    resident details
                                                                </p>
                                                            </TooltipContent>
                                                        )}
                                                    </Tooltip>
                                                </TooltipProvider>
                                                <TooltipProvider>
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <span className="inline-block">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => onEdit(resident)}
                                                                    disabled={!hasPermission('edit-residents')}
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </span>
                                                        </TooltipTrigger>
                                                        {!hasPermission('edit-residents') && (
                                                            <TooltipContent>
                                                                <p>
                                                                    You don't have permission to edit
                                                                    residents
                                                                </p>
                                                            </TooltipContent>
                                                        )}
                                                    </Tooltip>
                                                </TooltipProvider>
                                                <TooltipProvider>
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <span className="inline-block">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        onDelete(resident)
                                                                    }
                                                                    disabled={!hasPermission('delete-residents')}
                                                                >
                                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                                </Button>
                                                            </span>
                                                        </TooltipTrigger>
                                                        {!hasPermission('delete-residents') && (
                                                            <TooltipContent>
                                                                <p>
                                                                    You don't have permission to delete
                                                                    residents
                                                                </p>
                                                            </TooltipContent>
                                                        )}
                                                    </Tooltip>
                                                </TooltipProvider>
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
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

