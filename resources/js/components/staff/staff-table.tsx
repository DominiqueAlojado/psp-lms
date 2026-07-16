import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Building2, FileText, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '../delete-confirmation-dialog';

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

interface StaffTableProps {
    staff: Staff[];
    onEdit: (staff: Staff) => void;
    onViewLogs: (staff: Staff) => void;
}

export function StaffTable({ staff, onEdit, onViewLogs }: StaffTableProps) {
    const { hasPermission } = usePermissions();
    const [deletingStaff, setDeletingStaff] = useState<Staff | null>(null);

    const handleDelete = () => {
        if (!deletingStaff) return;

        router.delete(`/staff/${deletingStaff.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeletingStaff(null);
            },
            onError: (errors) => {
                const errorMessage =
                    errors.error ||
                    Object.values(errors)[0] ||
                    'Failed to delete staff member';
                toast.error(errorMessage);
            },
            onFinish: () => {
                setDeletingStaff(null);
            },
        });
    };

    if (staff.length === 0) {
        return (
            <div className="rounded-md border p-8 text-center">
                <p className="text-sm text-muted-foreground">
                    No staff members found.
                </p>
            </div>
        );
    }

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Roles</TableHead>
                        <TableHead>Current Organization</TableHead>
                        <TableHead>Organizations</TableHead>
                        <TableHead>Updated</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {staff.map((member) => (
                        <TableRow key={member.id}>
                            <TableCell className="font-medium">
                                {member.name}
                            </TableCell>
                            <TableCell className="text-sm text-muted-foreground">
                                {member.email}
                            </TableCell>
                            <TableCell>
                                <div className="flex flex-wrap gap-1">
                                    {member.roles.map((role) => (
                                        <Badge
                                            key={role}
                                            variant={
                                                role === 'System Admin'
                                                    ? 'destructive'
                                                    : role === 'Admin'
                                                      ? 'default'
                                                      : 'secondary'
                                            }
                                        >
                                            {role}
                                        </Badge>
                                    ))}
                                </div>
                            </TableCell>
                            <TableCell className="text-sm">
                                {member.current_organization}
                            </TableCell>
                            <TableCell>
                                <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                    <Building2 className="h-3 w-3" />
                                    {member.organizations_count}
                                </div>
                            </TableCell>
                            <TableCell className="text-sm text-muted-foreground">
                                {formatRelativeTime(member.updated_at)}
                            </TableCell>
                            <TableCell className="text-right">
                                <div className="flex justify-end gap-2">
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <span className="inline-block">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            onViewLogs(member)
                                                        }
                                                    >
                                                        <FileText className="h-4 w-4" />
                                                    </Button>
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>View activity logs</p>
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
                                                            onEdit(member)
                                                        }
                                                        disabled={
                                                            !hasPermission(
                                                                'edit-staff',
                                                            )
                                                        }
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                </span>
                                            </TooltipTrigger>
                                            {!hasPermission('edit-staff') && (
                                                <TooltipContent>
                                                    <p>
                                                        You don't have
                                                        permission to edit staff
                                                        members
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
                                                            setDeletingStaff(
                                                                member,
                                                            )
                                                        }
                                                        disabled={
                                                            !hasPermission(
                                                                'delete-staff',
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </span>
                                            </TooltipTrigger>
                                            {!hasPermission('delete-staff') && (
                                                <TooltipContent>
                                                    <p>
                                                        You don't have
                                                        permission to delete
                                                        staff members
                                                    </p>
                                                </TooltipContent>
                                            )}
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>

            <DeleteConfirmationDialog
                open={deletingStaff !== null}
                title="Delete Staff Member?"
                itemName={
                    deletingStaff
                        ? `${deletingStaff.name} (${deletingStaff.email})`
                        : undefined
                }
                warningMessage="This action cannot be undone. This will permanently delete this staff member from the system."
                confirmText="Delete Staff"
                onConfirm={handleDelete}
                onCancel={() => setDeletingStaff(null)}
            />
        </div>
    );
}
