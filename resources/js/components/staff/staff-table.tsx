import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
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
import { usePermissions } from '@/hooks/use-permissions';
import { router } from '@inertiajs/react';
import { Building2, Pencil, Trash2 } from 'lucide-react';
import { toast } from 'sonner';

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
}

export function StaffTable({ staff, onEdit }: StaffTableProps) {
    const { hasPermission } = usePermissions();

    const handleDelete = (id: number) => {
        router.delete(`/staff/${id}`, {
            preserveScroll: true,
            onError: (errors) => {
                const errorMessage =
                    errors.error ||
                    Object.values(errors)[0] ||
                    'Failed to delete staff member';
                toast.error(errorMessage);
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
                                {member.updated_at}
                            </TableCell>
                            <TableCell className="text-right">
                                <div className="flex justify-end gap-2">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => onEdit(member)}
                                        disabled={!hasPermission('edit-staff')}
                                    >
                                        <Pencil className="h-4 w-4" />
                                    </Button>
                                    <AlertDialog>
                                        <AlertDialogTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                disabled={
                                                    !hasPermission(
                                                        'delete-staff',
                                                    )
                                                }
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </AlertDialogTrigger>
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>
                                                    Delete Staff Member
                                                </AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Are you sure you want to
                                                    delete{' '}
                                                    <span className="font-semibold">
                                                        {member.name}
                                                    </span>
                                                    ? This action cannot be
                                                    undone.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>
                                                    Cancel
                                                </AlertDialogCancel>
                                                <AlertDialogAction
                                                    onClick={() =>
                                                        handleDelete(member.id)
                                                    }
                                                    className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                >
                                                    Delete
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
