import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
                onSuccess: () => {
                    toast.success('Organization added successfully');
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

    const handleRemoveOrganization = (organizationId: number, organizationName: string) => {
        if (organizationId === homeOrganizationId) {
            toast.error('Cannot remove resident from their home organization');
            return;
        }

        if (confirm(`Remove ${residentName} from ${organizationName}?`)) {
            router.delete(
                `/residents/${residentId}/organizations`,
                {
                    data: { organization_id: organizationId },
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success('Organization removed successfully');
                        // Refresh organization lists
                        onUpdate?.();
                    },
                    onError: (errors) => {
                        if (errors.error) {
                            toast.error(errors.error as string);
                        } else {
                            toast.error('Failed to remove organization');
                        }
                    },
                },
            );
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Associated Institutions</CardTitle>
                <CardDescription>
                    Manage the institutions this resident is associated with for training and rotations
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {/* Current Organizations */}
                <div className="space-y-3">
                    <Label>Current Institutions ({currentOrganizations.length})</Label>
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
                                                (Home)
                                            </span>
                                        )}
                                        {!isHome && (
                                            <button
                                                onClick={() =>
                                                    handleRemoveOrganization(
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
                        <Label>Add Institution</Label>
                        <div className="flex gap-2">
                            <select
                                value={selectedOrganizationId || ''}
                                onChange={(e) =>
                                    setSelectedOrganizationId(
                                        e.target.value ? Number(e.target.value) : null,
                                    )
                                }
                                className="flex h-10 flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option value="">Select an institution...</option>
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
        </Card>
    );
}

