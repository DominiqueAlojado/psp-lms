import AlertError from '@/components/alert-error';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Edit, Plus, Shield, Trash2 } from 'lucide-react';
import { useState } from 'react';

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
}

interface Props {
    roles: Role[];
    permissions: Permission[];
}

export default function RolesPermissions() {
    const { roles, permissions } = usePage<Props>().props;
    const [editingRole, setEditingRole] = useState<Role | null>(null);
    const [addingRole, setAddingRole] = useState(false);
    const [editingPermission, setEditingPermission] = useState<Permission | null>(null);
    const [addingPermission, setAddingPermission] = useState(false);
    const [assigningPermissions, setAssigningPermissions] = useState<Role | null>(null);
    const [selectedPermissions, setSelectedPermissions] = useState<number[]>([]);

    const handleDeleteRole = (roleId: number, roleName: string) => {
        if (confirm(`Are you sure you want to delete the role "${roleName}"?`)) {
            router.delete(`/settings/roles/${roleId}`, {
                preserveScroll: true,
            });
        }
    };

    const handleDeletePermission = (permissionId: number, permissionName: string) => {
        if (confirm(`Are you sure you want to delete the permission "${permissionName}"?`)) {
            router.delete(`/settings/permissions/${permissionId}`, {
                preserveScroll: true,
            });
        }
    };

    const openAssignPermissions = (role: Role) => {
        setAssigningPermissions(role);
        // Get IDs of permissions the role already has
        const rolePermissionIds = permissions
            .filter(p => role.permissions.includes(p.name))
            .map(p => p.id);
        setSelectedPermissions(rolePermissionIds);
    };

    const handleAssignPermissions = () => {
        if (!assigningPermissions) return;

        router.post(`/settings/roles/${assigningPermissions.id}/permissions`, {
            permissions: selectedPermissions,
        }, {
            preserveScroll: true,
            onSuccess: () => setAssigningPermissions(null),
        });
    };

    const togglePermission = (permissionId: number) => {
        setSelectedPermissions(prev =>
            prev.includes(permissionId)
                ? prev.filter(id => id !== permissionId)
                : [...prev, permissionId]
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

                    <Tabs defaultValue="roles" className="w-full">
                        <TabsList className="grid w-full grid-cols-3">
                            <TabsTrigger value="roles">Roles ({roles.length})</TabsTrigger>
                            <TabsTrigger value="permissions">Permissions ({permissions.length})</TabsTrigger>
                            <TabsTrigger value="assign">Assign Permissions</TabsTrigger>
                        </TabsList>

                        {/* Tab 1: Roles */}
                        <TabsContent value="roles" className="space-y-4 pt-4">
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle>Roles</CardTitle>
                                            <CardDescription>
                                                Manage system roles
                                            </CardDescription>
                                        </div>
                                        <Button onClick={() => setAddingRole(true)}>
                                            <Plus className="mr-2 h-4 w-4" />
                                            Add Role
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Role Name</TableHead>
                                                <TableHead>Permissions</TableHead>
                                                <TableHead className="w-[150px]">Actions</TableHead>
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
                                                            {role.permissions_count} permissions
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex gap-2">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => setEditingRole(role)}
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDeleteRole(role.id, role.name)}
                                                            >
                                                                <Trash2 className="h-4 w-4 text-destructive" />
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Tab 2: Permissions */}
                        <TabsContent value="permissions" className="space-y-4 pt-4">
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle>Permissions</CardTitle>
                                            <CardDescription>
                                                Manage system permissions
                                            </CardDescription>
                                        </div>
                                        <Button onClick={() => setAddingPermission(true)}>
                                            <Plus className="mr-2 h-4 w-4" />
                                            Add Permission
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Permission Name</TableHead>
                                                <TableHead className="w-[150px]">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {permissions.map((permission) => (
                                                <TableRow key={permission.id}>
                                                    <TableCell className="font-medium">
                                                        {permission.name}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex gap-2">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => setEditingPermission(permission)}
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDeletePermission(permission.id, permission.name)}
                                                            >
                                                                <Trash2 className="h-4 w-4 text-destructive" />
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Tab 3: Assign Permissions */}
                        <TabsContent value="assign" className="space-y-4 pt-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Assign Permissions to Roles</CardTitle>
                                    <CardDescription>
                                        Select a role to manage its permissions
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                        {roles.map((role) => (
                                            <div
                                                key={role.id}
                                                className="rounded-lg border p-4 hover:bg-muted/50 cursor-pointer transition-colors"
                                                onClick={() => openAssignPermissions(role)}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <h3 className="font-medium">{role.name}</h3>
                                                        <p className="text-sm text-muted-foreground">
                                                            {role.permissions_count} permissions
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
                    <SheetContent className="sm:max-w-[500px] p-0">
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
                                    const formData = new FormData(e.currentTarget);
                                    router.post('/settings/roles', Object.fromEntries(formData), {
                                        preserveScroll: true,
                                        onSuccess: () => setAddingRole(false),
                                    });
                                }}
                            >
                                {(() => {
                                    const { errors } = usePage<any>().props;
                                    return (
                                        <div className="space-y-6">
                                            {Object.keys(errors).length > 0 && (
                                                <AlertError errors={Object.values(errors)} />
                                            )}

                                            <div className="space-y-2">
                                                <Label htmlFor="role_name">Role Name</Label>
                                                <Input
                                                    id="role_name"
                                                    name="name"
                                                    placeholder="e.g., Content Manager"
                                                    required
                                                />
                                            </div>

                                            <div className="flex justify-end gap-3 pt-4 border-t">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() => setAddingRole(false)}
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
                <Sheet open={!!editingRole} onOpenChange={(open) => !open && setEditingRole(null)}>
                    <SheetContent className="sm:max-w-[500px] p-0">
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
                                        const formData = new FormData(e.currentTarget);
                                        router.patch(`/settings/roles/${editingRole.id}`, Object.fromEntries(formData), {
                                            preserveScroll: true,
                                            onSuccess: () => setEditingRole(null),
                                        });
                                    }}
                                >
                                    {(() => {
                                        const { errors } = usePage<any>().props;
                                        return (
                                            <div className="space-y-6">
                                                {Object.keys(errors).length > 0 && (
                                                    <AlertError errors={Object.values(errors)} />
                                                )}

                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_role_name">Role Name</Label>
                                                    <Input
                                                        id="edit_role_name"
                                                        name="name"
                                                        defaultValue={editingRole.name}
                                                        required
                                                    />
                                                </div>

                                                <div className="flex justify-end gap-3 pt-4 border-t">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={() => setEditingRole(null)}
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
                <Sheet open={addingPermission} onOpenChange={setAddingPermission}>
                    <SheetContent className="sm:max-w-[500px] p-0">
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
                                    const formData = new FormData(e.currentTarget);
                                    router.post('/settings/permissions', Object.fromEntries(formData), {
                                        preserveScroll: true,
                                        onSuccess: () => setAddingPermission(false),
                                    });
                                }}
                            >
                                {(() => {
                                    const { errors } = usePage<any>().props;
                                    return (
                                        <div className="space-y-6">
                                            {Object.keys(errors).length > 0 && (
                                                <AlertError errors={Object.values(errors)} />
                                            )}

                                            <div className="space-y-2">
                                                <Label htmlFor="permission_name">Permission Name</Label>
                                                <Input
                                                    id="permission_name"
                                                    name="name"
                                                    placeholder="e.g., manage-content"
                                                    required
                                                />
                                                <p className="text-xs text-muted-foreground">
                                                    Use kebab-case (e.g., view-users, edit-courses)
                                                </p>
                                            </div>

                                            <div className="flex justify-end gap-3 pt-4 border-t">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() => setAddingPermission(false)}
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
                <Sheet open={!!editingPermission} onOpenChange={(open) => !open && setEditingPermission(null)}>
                    <SheetContent className="sm:max-w-[500px] p-0">
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
                                        const formData = new FormData(e.currentTarget);
                                        router.patch(`/settings/permissions/${editingPermission.id}`, Object.fromEntries(formData), {
                                            preserveScroll: true,
                                            onSuccess: () => setEditingPermission(null),
                                        });
                                    }}
                                >
                                    {(() => {
                                        const { errors } = usePage<any>().props;
                                        return (
                                            <div className="space-y-6">
                                                {Object.keys(errors).length > 0 && (
                                                    <AlertError errors={Object.values(errors)} />
                                                )}

                                                <div className="space-y-2">
                                                    <Label htmlFor="edit_permission_name">Permission Name</Label>
                                                    <Input
                                                        id="edit_permission_name"
                                                        name="name"
                                                        defaultValue={editingPermission.name}
                                                        required
                                                    />
                                                </div>

                                                <div className="flex justify-end gap-3 pt-4 border-t">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={() => setEditingPermission(null)}
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
                <Sheet open={!!assigningPermissions} onOpenChange={(open) => !open && setAssigningPermissions(null)}>
                    <SheetContent className="sm:max-w-[600px] p-0 overflow-y-auto">
                        <div className="p-6">
                            <SheetHeader className="pb-6">
                                <SheetTitle>
                                    Assign Permissions to {assigningPermissions?.name}
                                </SheetTitle>
                                <SheetDescription>
                                    Select which permissions this role should have
                                </SheetDescription>
                            </SheetHeader>

                            {assigningPermissions && (
                                <div className="space-y-6">
                                    <div className="rounded-lg border p-4">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="font-medium">{assigningPermissions.name}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {selectedPermissions.length} of {permissions.length} permissions selected
                                                </p>
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    if (selectedPermissions.length === permissions.length) {
                                                        setSelectedPermissions([]);
                                                    } else {
                                                        setSelectedPermissions(permissions.map(p => p.id));
                                                    }
                                                }}
                                            >
                                                {selectedPermissions.length === permissions.length ? 'Deselect All' : 'Select All'}
                                            </Button>
                                        </div>
                                    </div>

                                    <div className="space-y-2 max-h-[400px] overflow-y-auto rounded-lg border p-4">
                                        {permissions.map((permission) => (
                                            <div key={permission.id} className="flex items-center space-x-2 py-2">
                                                <Checkbox
                                                    id={`perm-${permission.id}`}
                                                    checked={selectedPermissions.includes(permission.id)}
                                                    onCheckedChange={() => togglePermission(permission.id)}
                                                />
                                                <Label
                                                    htmlFor={`perm-${permission.id}`}
                                                    className="flex-1 cursor-pointer"
                                                >
                                                    {permission.name}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="flex justify-end gap-3 pt-4 border-t">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setAssigningPermissions(null)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button onClick={handleAssignPermissions}>
                                            Save Permissions
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </SheetContent>
                </Sheet>
            </SettingsLayout>
        </AppLayout>
    );
}

