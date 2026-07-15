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
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import { Building2, Plus, X } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface Organization {
    id: number;
    name: string;
    slug: string;
    type: string;
    pivot?: {
        joined_at: string;
        is_active: boolean;
    };
    membership?: {
        started_at: string | null;
        ended_at: string | null;
        is_primary: boolean;
    };
}

interface Props {
    residentId: number;
    residentName: string;
    homeOrganizationId: number;
    currentOrganizations: Organization[];
    availableOrganizations: Organization[];
    onUpdate?: () => void;
}

export function ResidentOrganizations({
    residentId,
    residentName,
    homeOrganizationId,
    currentOrganizations,
    availableOrganizations,
    onUpdate,
}: Props) {
    const [selectedOrganizationId, setSelectedOrganizationId] = useState<
        number | null
    >(null);
    const [removingOrganization, setRemovingOrganization] = useState<{
        id: number;
        name: string;
    } | null>(null);

    const handleAddOrganization = () => {
        if (!selectedOrganizationId) {
            toast.error('Please select an organization');
            return;
        }

        router.post(
            `/residents/${residentId}/organizations`,
            { organization_id: selectedOrganizationId },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    // Toast is shown by global flash handler
                    setSelectedOrganizationId(null);
                    // Refresh organization lists
                    onUpdate?.();
                },
                onError: (errors) => {
                    if (errors.error) {
                        toast.error(errors.error as string);
                    } else {
                        toast.error('Failed to add organization');
                    }
                },
            },
        );
    };

    const handleRemoveClick = (
        organizationId: number,
        organizationName: string,
    ) => {
        if (organizationId === homeOrganizationId) {
            toast.error('Cannot remove resident from their home organization');
            return;
        }

        setRemovingOrganization({ id: organizationId, name: organizationName });
    };

    const confirmRemoveOrganization = () => {
        if (!removingOrganization) return;

        router.delete(`/residents/${residentId}/organizations`, {
            data: { organization_id: removingOrganization.id },
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                // Toast is shown by global flash handler
                // Sheet stays open, just refresh the lists
                onUpdate?.();
            },
            onError: (errors) => {
                if (errors.error) {
                    toast.error(errors.error as string);
                } else {
                    toast.error('Failed to remove organization');
                }
            },
            onFinish: () => {
                // Close only the confirmation dialog, not the sheet
                setRemovingOrganization(null);
            },
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Current Organization Access</CardTitle>
                <CardDescription>
                    Manage the active organizations this resident can currently access
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {/* Current Organizations */}
                <div className="space-y-3">
                    <Label>
                        Active Organizations ({currentOrganizations.length})
                    </Label>
                    {currentOrganizations.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No institutions associated
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {currentOrganizations.map((org) => {
                                const isHome = org.id === homeOrganizationId;
                                return (
                                    <Badge
                                        key={org.id}
                                        variant={isHome ? 'default' : 'outline'}
                                        className="flex items-center gap-1 pr-1"
                                    >
                                        <Building2 className="h-3 w-3" />
                                        <span>{org.name}</span>
                                        {isHome && (
                                            <span className="ml-1 text-xs opacity-70">
                                                (Current home)
                                            </span>
                                        )}
                                        {!isHome && (
                                            <button
                                                onClick={() =>
                                                    handleRemoveClick(
                                                        org.id,
                                                        org.name,
                                                    )
                                                }
                                                className="ml-1 rounded-full p-0.5 hover:bg-destructive/20"
                                            >
                                                <X className="h-3 w-3" />
                                            </button>
                                        )}
                                    </Badge>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* Add New Organization */}
                {availableOrganizations.length > 0 && (
                    <div className="space-y-3 border-t pt-4">
                        <Label>Add Active Organization</Label>
                        <div className="flex gap-2">
                            <select
                                value={selectedOrganizationId || ''}
                                onChange={(e) =>
                                    setSelectedOrganizationId(
                                        e.target.value
                                            ? Number(e.target.value)
                                            : null,
                                    )
                                }
                                className="flex h-10 flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">
                                    Select an institution...
                                </option>
                                {availableOrganizations.map((org) => (
                                    <option key={org.id} value={org.id}>
                                        {org.name} ({org.type})
                                    </option>
                                ))}
                            </select>
                            <Button
                                onClick={handleAddOrganization}
                                disabled={!selectedOrganizationId}
                                size="sm"
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Add
                            </Button>
                        </div>
                    </div>
                )}
            </CardContent>

            {/* Remove Organization Confirmation Dialog */}
            <AlertDialog
                open={!!removingOrganization}
                onOpenChange={(open) => !open && setRemovingOrganization(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Remove Institution</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to remove{' '}
                            <strong>{residentName}</strong> from{' '}
                            <strong>{removingOrganization?.name}</strong>? This
                            will remove their association with this institution.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmRemoveOrganization}
                            className="bg-destructive text-white hover:bg-destructive/90"
                        >
                            Remove
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </Card>
    );
}
