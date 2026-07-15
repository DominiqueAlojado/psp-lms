import AlertError from '@/components/alert-error';
import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
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
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Edit, Plus, Search, Shield, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles & Permissions',
        href: '/settings/roles-permissions',
    },
];

interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions_count: number;
    permissions: string[];
}

interface Permission {
    id: number;
    name: string;
    guard_name: string;
    category: string;
    display_order: number;
}

interface Props {
    roles: Role[];
    permissions: Permission[];
    groupedPermissions: Record<string, Permission[]>;
    errors?: Record<string, string>;
}

export default function RolesPermissions() {
    const { roles, permissions, groupedPermissions, errors = {} } =
        usePage<Props>().props;
    const [editingRole, setEditingRole] = useState<Role | null>(null);
    const [addingRole, setAddingRole] = useState(false);
    const [editingPermission, setEditingPermission] =
        useState<Permission | null>(null);
    const [addingPermission, setAddingPermission] = useState(false);
    const [assigningPermissions, setAssigningPermissions] =
        useState<Role | null>(null);
    const [selectedPermissions, setSelectedPermissions] = useState<number[]>(
        [],
    );
    const [permissionSearch, setPermissionSearch] = useState('');
    const [assignPermissionSearch, setAssignPermissionSearch] = useState('');
    const [deletingRole, setDeletingRole] = useState<{
        id: number;
        name: string;
    } | null>(null);
    const [deletingPermission, setDeletingPermission] = useState<{
        id: number;
        name: string;
    } | null>(null);

    const filteredPermissions = useMemo(
        () =>
            permissions.filter((p) =>
                p.name
                    .toLowerCase()
                    .includes(permissionSearch.trim().toLowerCase()),
            ),
        [permissions, permissionSearch],
    );

    const filteredGroupedPermissions = useMemo(() => {
        const query = assignPermissionSearch.trim().toLowerCase();
        if (!query) {
            return groupedPermissions;
        }
        const result: Record<string, Permission[]> = {};
        for (const [category, perms] of Object.entries(groupedPermissions)) {
            const filtered = perms.filter((p) =>
                p.name.toLowerCase().includes(query),
            );
            if (filtered.length > 0) {
                result[category] = filtered;
            }
        }
        return result;
    }, [groupedPermissions, assignPermissionSearch]);

    const confirmDeleteRole = () => {
        if (!deletingRole) return;
        router.delete(`/settings/roles/${deletingRole.id}`, {
            preserveScroll: true,
            onFinish: () => setDeletingRole(null),
        });
    };

    const confirmDeletePermission = () => {
        if (!deletingPermission) return;
        router.delete(`/settings/permissions/${deletingPermission.id}`, {
            preserveScroll: true,
            onFinish: () => setDeletingPermission(null),
        });
    };

    const openAssignPermissions = (role: Role) => {
        setAssigningPermissions(role);
        // Get IDs of permissions the role already has
        const rolePermissionIds = permissions
            .filter((p) => role.permissions.includes(p.name))
            .map((p) => p.id);
        setSelectedPermissions(rolePermissionIds);
    };

    const handleAssignPermissions = () => {
        if (!assigningPermissions) return;

        router.post(
            `/settings/roles/${assigningPermissions.id}/permissions`,
            {
                permissions: selectedPermissions,
            },
            {
                preserveScroll: true,
                onSuccess: () => setAssigningPermissions(null),
            },
        );
    };

    const togglePermission = (permissionId: number) => {
        setSelectedPermissions((prev) =>
            prev.includes(permissionId)
                ? prev.filter((id) => id !== permissionId)
                : [...prev, permissionId],
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles & Permissions" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Roles & Permissions"
                        description="Manage system roles and permissions"
                    />

                    <div className="grid gap-4 md:grid-cols-3">
                        <StatCard
                            title="Roles"
                            value={roles.length}
                            description="Permission bundles available across the system"
                            icon={Shield}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Permissions"
                            value={permissions.length}
                            description="Granular access rules available for assignment"
                            icon={Search}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Categories"
                            value={Object.keys(groupedPermissions).length}
                            description="Permission groups currently organized in settings"
                            icon={Plus}
                            iconColor="text-primary"
                        />
                    </div>

                    <Tabs defaultValue="roles" className="w-full">
                        <div className="overflow-x-auto pb-1">
                            <TabsList className="grid min-w-[32rem] grid-cols-3 lg:w-fit lg:min-w-[34rem]">
                                <TabsTrigger value="roles">
                                    Roles ({roles.length})
                                </TabsTrigger>
                                <TabsTrigger value="permissions">
                                    Permissions ({permissions.length})
                                </TabsTrigger>
                                <TabsTrigger value="assign">
                                    Assign Permissions
                                </TabsTrigger>
                            </TabsList>
                        </div>

                        {/* Tab 1: Roles */}
                        <TabsContent value="roles" className="space-y-4 pt-4">
                            <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                                <CardHeader className="pb-3">
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <CardTitle>Roles</CardTitle>
                                            <CardDescription>
                                                Manage system roles
                                            </CardDescription>
                                        </div>
                                        <Button
                                            onClick={() => setAddingRole(true)}
                                        >
                                            <Plus className="mr-2 h-4 w-4" />
                                            Add Role
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Role Name</TableHead>
                                                    <TableHead>
                                                        Permissions
                                                    </TableHead>
                                                    <TableHead className="w-[150px]">
                                                        Actions
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {roles.map((role) => (
                                                    <TableRow key={role.id}>
                                                        <TableCell className="font-medium">
                                                            <div className="flex items-center gap-2">
                                                                <Shield className="h-4 w-4 text-muted-foreground" />
                                                                {role.name}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge variant="secondary">
                                                                {
                                                                    role.permissions_count
                                                                }{' '}
                                                                permissions
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex gap-2">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        setEditingRole(
                                                                            role,
                                                                        )
                                                                    }
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        setDeletingRole(
                                                                            {
                                                                                id: role.id,
                                                                                name: role.name,
                                                                            },
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                                </Button>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Tab 2: Permissions */}
                        <TabsContent
                            value="permissions"
                            className="space-y-4 pt-4"
                        >
                            <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                                <CardHeader className="pb-3">
                                    <div className="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                        <div>
                                            <CardTitle>Permissions</CardTitle>
                                            <CardDescription>
                                                Manage system permissions
                                            </CardDescription>
                                        </div>
                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                            <div className="relative w-full sm:w-64">
                                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                                <Input
                                                    placeholder="Search permissions..."
                                                    value={permissionSearch}
                                                    onChange={(e) =>
                                                        setPermissionSearch(
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full pl-9"
                                                />
                                            </div>
                                            <Button
                                                onClick={() =>
                                                    setAddingPermission(true)
                                                }
                                            >
                                                <Plus className="mr-2 h-4 w-4" />
                                                Add Permission
                                            </Button>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>
                                                        Permission Name
                                                    </TableHead>
                                                    <TableHead className="w-[150px]">
                                                        Actions
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {filteredPermissions.map(
                                                    (permission) => (
                                                        <TableRow
                                                            key={permission.id}
                                                        >
                                                            <TableCell className="font-medium">
                                                                {permission.name}
                                                            </TableCell>
                                                            <TableCell>
                                                                <div className="flex gap-2">
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            setEditingPermission(
                                                                                permission,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Edit className="h-4 w-4" />
                                                                    </Button>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            setDeletingPermission(
                                                                                {
                                                                                    id: permission.id,
                                                                                    name: permission.name,
                                                                                },
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                                    </Button>
                                                                </div>
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Tab 3: Assign Permissions */}
                        <TabsContent value="assign" className="space-y-4 pt-4">
                            <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                                <CardHeader className="pb-3">
                                    <CardTitle>
                                        Assign Permissions to Roles
                                    </CardTitle>
                                    <CardDescription>
                                        Select a role to manage its permissions
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                        {roles.map((role) => (
                                            <div
                                                key={role.id}
                                                className="cursor-pointer rounded-lg border p-4 transition-colors hover:bg-muted/50"
                                                onClick={() =>
                                                    openAssignPermissions(role)
                                                }
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <h3 className="font-medium">
                                                            {role.name}
                                                        </h3>
                                                        <p className="text-sm text-muted-foreground">
                                                            {
                                                                role.permissions_count
                                                            }{' '}
                                                            permissions
                                                        </p>
                                                    </div>
                                                    <Shield className="h-5 w-5 text-muted-foreground" />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>

                {/* Add Role Sheet */}
                <Sheet open={addingRole} onOpenChange={setAddingRole}>
                    <SheetContent className="p-0 sm:max-w-[500px]">
                        <div className="p-6">
                            <SheetHeader className="pb-6">
                                <SheetTitle>Add New Role</SheetTitle>
                                <SheetDescription>
                                    Create a new role for the system
                                </SheetDescription>
                            </SheetHeader>

                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    const formData = new FormData(
                                        e.currentTarget,
                                    );
                                    router.post(
                                        '/settings/roles',
                                        Object.fromEntries(formData),
                                        {
                                            preserveScroll: true,
                                            onSuccess: () =>
                                                setAddingRole(false),
                                        },
                                    );
                                }}
                            >
                                {(() => {
                                    return (
                                        <div className="space-y-6">
                                            {Object.keys(errors).length > 0 && (
                                                <AlertError
                                                    errors={Object.values(
                                                        errors,
                                                    )}
                                                />
                                            )}

                                            <div className="space-y-2">
                                                <Label htmlFor="role_name">
                                                    Role Name
                                                </Label>
                                                <Input
                                                    id="role_name"
                                                    name="name"
                                                    placeholder="e.g., Content Manager"
                                                    required
                                                />
                                            </div>

                                            <div className="flex justify-end gap-3 border-t pt-4">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() =>
                                                        setAddingRole(false)
                                                    }
                                                >
                                                    Cancel
                                                </Button>
                                                <Button type="submit">
                                                    Create Role
                                                </Button>
                                            </div>
                                        </div>
                                    );
                                })()}
                            </form>
                        </div>
                    </SheetContent>
                </Sheet>

                {/* Edit Role Sheet */}
                <Sheet
                    open={!!editingRole}
                    onOpenChange={(open) => !open && setEditingRole(null)}
                >
                    <SheetContent className="p-0 sm:max-w-[500px]">
                        <div className="p-6">
                            <SheetHeader className="pb-6">
                                <SheetTitle>Edit Role</SheetTitle>
                                <SheetDescription>
                                    Update role information
                                </SheetDescription>
                            </SheetHeader>

                            {editingRole && (
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        const formData = new FormData(
                                            e.currentTarget,
                                        );
                                        router.patch(
                                            `/settings/roles/${editingRole.id}`,
                                            Object.fromEntries(formData),
                                            {
                                                preserveScroll: true,
                                                onSuccess: () =>
                                                    setEditingRole(null),
                                            },
                                        );
                                    }}
                                >
                                    {(() => {
                                        return (
                                            <div className="space-y-6">
                                                {Object.keys(errors).length >
                                                    0 && (
                                                    <AlertError
                                                        errors={Object.values(
                                                            errors,
                                                        )}
                                                    />
                                                )}

                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_role_name">
                                                        Role Name
                                                    </Label>
                                                    <Input
                                                        id="edit_role_name"
                                                        name="name"
                                                        defaultValue={
                                                            editingRole.name
                                                        }
                                                        required
                                                    />
                                                </div>

                                                <div className="flex justify-end gap-3 border-t pt-4">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={() =>
                                                            setEditingRole(null)
                                                        }
                                                    >
                                                        Cancel
                                                    </Button>
                                                    <Button type="submit">
                                                        Save Changes
                                                    </Button>
                                                </div>
                                            </div>
                                        );
                                    })()}
                                </form>
                            )}
                        </div>
                    </SheetContent>
                </Sheet>

                {/* Add Permission Sheet */}
                <Sheet
                    open={addingPermission}
                    onOpenChange={setAddingPermission}
                >
                    <SheetContent className="p-0 sm:max-w-[500px]">
                        <div className="p-6">
                            <SheetHeader className="pb-6">
                                <SheetTitle>Add New Permission</SheetTitle>
                                <SheetDescription>
                                    Create a new permission for the system
                                </SheetDescription>
                            </SheetHeader>

                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    const formData = new FormData(
                                        e.currentTarget,
                                    );
                                    router.post(
                                        '/settings/permissions',
                                        Object.fromEntries(formData),
                                        {
                                            preserveScroll: true,
                                            onSuccess: () =>
                                                setAddingPermission(false),
                                        },
                                    );
                                }}
                            >
                                {(() => {
                                    const categories =
                                        Object.keys(groupedPermissions);
                                    return (
                                        <div className="space-y-6">
                                            {Object.keys(errors).length > 0 && (
                                                <AlertError
                                                    errors={Object.values(
                                                        errors,
                                                    )}
                                                />
                                            )}

                                            <div className="space-y-2">
                                                <Label htmlFor="permission_name">
                                                    Permission Name
                                                </Label>
                                                <Input
                                                    id="permission_name"
                                                    name="name"
                                                    placeholder="e.g., manage-content"
                                                    required
                                                />
                                                <p className="text-xs text-muted-foreground">
                                                    Use kebab-case (e.g.,
                                                    view-users, edit-courses)
                                                </p>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="permission_category">
                                                    Category
                                                </Label>
                                                <select
                                                    id="permission_category"
                                                    name="category"
                                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                                    required
                                                >
                                                    <option value="">
                                                        Select category...
                                                    </option>
                                                    {categories.map((cat) => (
                                                        <option
                                                            key={cat}
                                                            value={cat}
                                                        >
                                                            {cat}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            <div className="flex justify-end gap-3 border-t pt-4">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() =>
                                                        setAddingPermission(
                                                            false,
                                                        )
                                                    }
                                                >
                                                    Cancel
                                                </Button>
                                                <Button type="submit">
                                                    Create Permission
                                                </Button>
                                            </div>
                                        </div>
                                    );
                                })()}
                            </form>
                        </div>
                    </SheetContent>
                </Sheet>

                {/* Edit Permission Sheet */}
                <Sheet
                    open={!!editingPermission}
                    onOpenChange={(open) => !open && setEditingPermission(null)}
                >
                    <SheetContent className="p-0 sm:max-w-[500px]">
                        <div className="p-6">
                            <SheetHeader className="pb-6">
                                <SheetTitle>Edit Permission</SheetTitle>
                                <SheetDescription>
                                    Update permission information
                                </SheetDescription>
                            </SheetHeader>

                            {editingPermission && (
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        const formData = new FormData(
                                            e.currentTarget,
                                        );
                                        router.patch(
                                            `/settings/permissions/${editingPermission.id}`,
                                            Object.fromEntries(formData),
                                            {
                                                preserveScroll: true,
                                                onSuccess: () =>
                                                    setEditingPermission(null),
                                            },
                                        );
                                    }}
                                >
                                    {(() => {
                                        const categories =
                                            Object.keys(groupedPermissions);
                                        return (
                                            <div className="space-y-6">
                                                {Object.keys(errors).length >
                                                    0 && (
                                                    <AlertError
                                                        errors={Object.values(
                                                            errors,
                                                        )}
                                                    />
                                                )}

                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_permission_name">
                                                        Permission Name
                                                    </Label>
                                                    <Input
                                                        id="edit_permission_name"
                                                        name="name"
                                                        defaultValue={
                                                            editingPermission.name
                                                        }
                                                        required
                                                    />
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_permission_category">
                                                        Category
                                                    </Label>
                                                    <select
                                                        id="edit_permission_category"
                                                        name="category"
                                                        defaultValue={
                                                            editingPermission.category
                                                        }
                                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                                        required
                                                    >
                                                        <option value="">
                                                            Select category...
                                                        </option>
                                                        {categories.map(
                                                            (cat) => (
                                                                <option
                                                                    key={cat}
                                                                    value={cat}
                                                                >
                                                                    {cat}
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                </div>

                                                <div className="flex justify-end gap-3 border-t pt-4">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={() =>
                                                            setEditingPermission(
                                                                null,
                                                            )
                                                        }
                                                    >
                                                        Cancel
                                                    </Button>
                                                    <Button type="submit">
                                                        Save Changes
                                                    </Button>
                                                </div>
                                            </div>
                                        );
                                    })()}
                                </form>
                            )}
                        </div>
                    </SheetContent>
                </Sheet>

                {/* Assign Permissions Sheet */}
                <Sheet
                    open={!!assigningPermissions}
                    onOpenChange={(open) =>
                        !open && setAssigningPermissions(null)
                    }
                >
                    <SheetContent className="overflow-y-auto p-0 sm:max-w-[600px]">
                        <div className="p-6">
                            <SheetHeader className="pb-6">
                                <SheetTitle>
                                    Assign Permissions to{' '}
                                    {assigningPermissions?.name}
                                </SheetTitle>
                                <SheetDescription>
                                    Select which permissions this role should
                                    have
                                </SheetDescription>
                            </SheetHeader>

                            {assigningPermissions && (
                                <div className="space-y-6">
                                    <div className="rounded-lg border p-4">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="font-medium">
                                                    {assigningPermissions.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {selectedPermissions.length}{' '}
                                                    of {permissions.length}{' '}
                                                    permissions selected
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <div className="relative">
                                                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                                    <Input
                                                        placeholder="Search permissions..."
                                                        value={
                                                            assignPermissionSearch
                                                        }
                                                        onChange={(e) =>
                                                            setAssignPermissionSearch(
                                                                e.target.value,
                                                            )
                                                        }
                                                        className="w-64 pl-9"
                                                    />
                                                </div>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => {
                                                        const visibleIds =
                                                            Object.values(
                                                                filteredGroupedPermissions,
                                                            ).flatMap((arr) =>
                                                                arr.map(
                                                                    (p) => p.id,
                                                                ),
                                                            );
                                                        const allVisibleSelected =
                                                            visibleIds.every(
                                                                (id) =>
                                                                    selectedPermissions.includes(
                                                                        id,
                                                                    ),
                                                            ) &&
                                                            visibleIds.length >
                                                                0;
                                                        if (
                                                            allVisibleSelected
                                                        ) {
                                                            setSelectedPermissions(
                                                                (prev) =>
                                                                    prev.filter(
                                                                        (id) =>
                                                                            !visibleIds.includes(
                                                                                id,
                                                                            ),
                                                                    ),
                                                            );
                                                        } else {
                                                            setSelectedPermissions(
                                                                (prev) => [
                                                                    ...new Set([
                                                                        ...prev,
                                                                        ...visibleIds,
                                                                    ]),
                                                                ],
                                                            );
                                                        }
                                                    }}
                                                >
                                                    {(() => {
                                                        const visibleIds =
                                                            Object.values(
                                                                filteredGroupedPermissions,
                                                            ).flatMap((arr) =>
                                                                arr.map(
                                                                    (p) => p.id,
                                                                ),
                                                            );
                                                        const allVisibleSelected =
                                                            visibleIds.every(
                                                                (id) =>
                                                                    selectedPermissions.includes(
                                                                        id,
                                                                    ),
                                                            ) &&
                                                            visibleIds.length >
                                                                0;
                                                        return allVisibleSelected
                                                            ? 'Deselect Filtered'
                                                            : 'Select Filtered';
                                                    })()}
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="max-h-[500px] space-y-4 overflow-y-auto rounded-lg border p-4">
                                        {Object.entries(
                                            filteredGroupedPermissions,
                                        ).map(
                                            ([
                                                category,
                                                categoryPermissions,
                                            ]) => {
                                                if (
                                                    categoryPermissions.length ===
                                                    0
                                                )
                                                    return null;

                                                // Check if all in this category are selected
                                                const allCategorySelected =
                                                    categoryPermissions.every(
                                                        (p) =>
                                                            selectedPermissions.includes(
                                                                p.id,
                                                            ),
                                                    );

                                                const toggleCategory = () => {
                                                    const categoryIds =
                                                        categoryPermissions.map(
                                                            (p) => p.id,
                                                        );
                                                    if (allCategorySelected) {
                                                        setSelectedPermissions(
                                                            (prev) =>
                                                                prev.filter(
                                                                    (id) =>
                                                                        !categoryIds.includes(
                                                                            id,
                                                                        ),
                                                                ),
                                                        );
                                                    } else {
                                                        setSelectedPermissions(
                                                            (prev) => [
                                                                ...new Set([
                                                                    ...prev,
                                                                    ...categoryIds,
                                                                ]),
                                                            ],
                                                        );
                                                    }
                                                };

                                                return (
                                                    <div
                                                        key={category}
                                                        className="space-y-2"
                                                    >
                                                        {/* Category Header */}
                                                        <div className="flex items-center space-x-2 border-b py-2">
                                                            <Checkbox
                                                                id={`category-${category}`}
                                                                checked={
                                                                    allCategorySelected
                                                                }
                                                                onCheckedChange={
                                                                    toggleCategory
                                                                }
                                                            />
                                                            <Label
                                                                htmlFor={`category-${category}`}
                                                                className="flex-1 cursor-pointer text-sm font-semibold"
                                                            >
                                                                {category} (
                                                                {
                                                                    categoryPermissions.length
                                                                }
                                                                )
                                                            </Label>
                                                        </div>

                                                        {/* Permissions in Category */}
                                                        <div className="ml-6 space-y-1">
                                                            {categoryPermissions.map(
                                                                (
                                                                    permission,
                                                                ) => (
                                                                    <div
                                                                        key={
                                                                            permission.id
                                                                        }
                                                                        className="flex items-center space-x-2 py-1.5"
                                                                    >
                                                                        <Checkbox
                                                                            id={`perm-${permission.id}`}
                                                                            checked={selectedPermissions.includes(
                                                                                permission.id,
                                                                            )}
                                                                            onCheckedChange={() =>
                                                                                togglePermission(
                                                                                    permission.id,
                                                                                )
                                                                            }
                                                                        />
                                                                        <Label
                                                                            htmlFor={`perm-${permission.id}`}
                                                                            className="flex-1 cursor-pointer text-sm"
                                                                        >
                                                                            {
                                                                                permission.name
                                                                            }
                                                                        </Label>
                                                                    </div>
                                                                ),
                                                            )}
                                                        </div>
                                                    </div>
                                                );
                                            },
                                        )}
                                    </div>

                                    <div className="flex justify-end gap-3 border-t pt-4">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                setAssigningPermissions(null)
                                            }
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            onClick={handleAssignPermissions}
                                        >
                                            Save Permissions
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </SheetContent>
                </Sheet>

                {/* Delete Role Confirmation */}
                <AlertDialog
                    open={!!deletingRole}
                    onOpenChange={(open) => !open && setDeletingRole(null)}
                >
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This will permanently delete the role "
                                {deletingRole?.name}". This action cannot be
                                undone.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction
                                onClick={confirmDeleteRole}
                                className="bg-destructive text-white hover:bg-destructive/90"
                            >
                                <Trash2 className="h-4" />
                                Delete
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>

                {/* Delete Permission Confirmation */}
                <AlertDialog
                    open={!!deletingPermission}
                    onOpenChange={(open) =>
                        !open && setDeletingPermission(null)
                    }
                >
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This will permanently delete the permission "
                                {deletingPermission?.name}". This action cannot
                                be undone.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction
                                onClick={confirmDeletePermission}
                                className="bg-destructive text-white hover:bg-destructive/90"
                            >
                                <Trash2 className="h-4" />
                                Delete
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </SettingsLayout>
        </AppLayout>
    );
}
