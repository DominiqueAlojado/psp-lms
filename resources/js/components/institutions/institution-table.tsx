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
import { formatRelativeTime } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { Edit, FileText, Trash2, Users } from 'lucide-react';

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
    filters: Record<string, string | undefined>;
    onEdit: (institution: Institution) => void;
    onDelete: (institution: Institution) => void;
    onViewLogs: (institution: Institution) => void;
}

export function InstitutionTable({
    institutions,
    filters,
    onEdit,
    onDelete,
    onViewLogs,
}: Props) {
    const { hasPermission } = usePermissions();

    return (
        <Card>
            <CardContent className="p-8">
                <div className="space-y-6">
                    {/* Results Count */}
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {institutions.from || 0} to{' '}
                            {institutions.to || 0} of {institutions.total}{' '}
                            institutions
                        </p>
                    </div>

                    {/* Table */}
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="py-4">Name</TableHead>
                                <TableHead className="py-4">Type</TableHead>
                                <TableHead className="py-4">
                                    Residents
                                </TableHead>
                                <TableHead className="py-4">Users</TableHead>
                                <TableHead className="py-4">Status</TableHead>
                                <TableHead className="py-4">Updated</TableHead>
                                <TableHead className="w-[100px] py-4">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {institutions.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-8 text-center text-muted-foreground"
                                    >
                                        No institutions found. Try adjusting
                                        your filters.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                institutions.data.map((institution) => (
                                    <TableRow key={institution.id}>
                                        <TableCell className="py-4">
                                            <div>
                                                <div className="font-medium">
                                                    {institution.name}
                                                </div>
                                                {institution.description && (
                                                    <div className="text-sm text-muted-foreground">
                                                        {institution.description.substring(
                                                            0,
                                                            60,
                                                        )}
                                                        {institution.description
                                                            .length > 60 &&
                                                            '...'}
                                                    </div>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="py-4">
                                            <Badge variant="outline">
                                                {institution.type
                                                    .charAt(0)
                                                    .toUpperCase() +
                                                    institution.type.slice(1)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="py-4">
                                            <div className="flex items-center gap-1">
                                                <Users className="h-4 w-4 text-muted-foreground" />
                                                <span>
                                                    {
                                                        institution.residents_count
                                                    }
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="py-4">
                                            {institution.users_count}
                                        </TableCell>
                                        <TableCell className="py-4">
                                            <Badge
                                                variant={
                                                    institution.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {institution.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="py-4">
                                            <span className="text-sm text-muted-foreground">
                                                {formatRelativeTime(institution.updated_at)}
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
                                                                    onClick={() =>
                                                                        onViewLogs(
                                                                            institution,
                                                                        )
                                                                    }
                                                                >
                                                                    <FileText className="h-4 w-4" />
                                                                </Button>
                                                            </span>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            <p>
                                                                View activity
                                                                logs
                                                            </p>
                                                        </TooltipContent>
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
                                                                        onEdit(
                                                                            institution,
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        !hasPermission(
                                                                            'edit-institutions',
                                                                        )
                                                                    }
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </span>
                                                        </TooltipTrigger>
                                                        {!hasPermission(
                                                            'edit-institutions',
                                                        ) && (
                                                            <TooltipContent>
                                                                <p>
                                                                    You don't
                                                                    have
                                                                    permission
                                                                    to edit
                                                                    institutions
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
                                                                        onDelete(
                                                                            institution,
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        !hasPermission(
                                                                            'delete-institutions',
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                                </Button>
                                                            </span>
                                                        </TooltipTrigger>
                                                        {!hasPermission(
                                                            'delete-institutions',
                                                        ) && (
                                                            <TooltipContent>
                                                                <p>
                                                                    You don't
                                                                    have
                                                                    permission
                                                                    to delete
                                                                    institutions
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
                    {institutions.last_page > 1 && (
                        <div className="flex items-center justify-center gap-2">
                            {institutions.links.map((link, index) => (
                                <Button
                                    key={index}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    disabled={!link.url}
                                    onClick={() => {
                                        if (link.url) {
                                            const url = new URL(link.url);
                                            const page =
                                                url.searchParams.get('page');
                                            router.get(
                                                '/institutions',
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
    );
}
