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
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import { ArrowRightLeft } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';

interface Organization {
    id: number;
    name: string;
    slug: string;
}

interface Resident {
    id: number;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    email: string;
    contact_number: string | null;
    course: string;
    year_level: string;
    status: string;
    full_name: string;
    organization: Organization;
}

interface Props {
    open: boolean;
    resident: Resident | null;
    organizations: Organization[];
    onClose: () => void;
}

export function TransferResidentDialog({
    open,
    resident,
    organizations,
    onClose,
}: Props) {
    const [selectedOrganizationId, setSelectedOrganizationId] = useState('');
    const [confirmOpen, setConfirmOpen] = useState(false);

    const availableOrganizations = useMemo(() => {
        if (!resident) {
            return [];
        }

        return organizations.filter(
            (organization) => organization.id !== resident.organization.id,
        );
    }, [organizations, resident]);

    const selectedOrganization = availableOrganizations.find(
        (organization) => organization.id === Number(selectedOrganizationId),
    );

    const handleClose = () => {
        setSelectedOrganizationId('');
        setConfirmOpen(false);
        onClose();
    };

    const handleTransfer = () => {
        if (!resident || !selectedOrganization) {
            toast.error('Please select a destination organization');
            return;
        }

        router.patch(
            `/residents/${resident.id}`,
            {
                organization_id: selectedOrganization.id,
                first_name: resident.first_name,
                middle_name: resident.middle_name || '',
                last_name: resident.last_name,
                email: resident.email,
                contact_number: resident.contact_number || '',
                course: resident.course,
                year_level: resident.year_level,
                status: resident.status,
                password: '',
                password_confirmation: '',
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    handleClose();
                },
                onError: () => {
                    toast.error('Failed to transfer resident');
                },
            },
        );
    };

    if (!resident) {
        return null;
    }

    return (
        <>
            <AlertDialog open={open} onOpenChange={(next) => !next && handleClose()}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Transfer Resident</AlertDialogTitle>
                        <AlertDialogDescription>
                            Move <strong>{resident.full_name}</strong> to a new home
                            organization. Historical memberships and records stay
                            preserved.
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <div className="space-y-4">
                        <div className="rounded-lg border bg-muted/30 p-4 text-sm">
                            <div className="font-medium">Current home organization</div>
                            <div className="mt-1 text-muted-foreground">
                                {resident.organization.name}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="transfer_organization_id">
                                New home organization
                            </Label>
                            <select
                                id="transfer_organization_id"
                                value={selectedOrganizationId}
                                onChange={(event) => setSelectedOrganizationId(event.target.value)}
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">Select organization...</option>
                                {availableOrganizations.map((organization) => (
                                    <option key={organization.id} value={organization.id}>
                                        {organization.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={handleClose}>Cancel</AlertDialogCancel>
                        <Button
                            type="button"
                            onClick={() => {
                                if (!selectedOrganization) {
                                    toast.error('Please select a destination organization');
                                    return;
                                }

                                setConfirmOpen(true);
                            }}
                            disabled={!selectedOrganization}
                        >
                            <ArrowRightLeft className="mr-2 h-4 w-4" />
                            Review Transfer
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <AlertDialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Confirm Transfer</AlertDialogTitle>
                        <AlertDialogDescription>
                            Transfer <strong>{resident.full_name}</strong> from{' '}
                            <strong>{resident.organization.name}</strong> to{' '}
                            <strong>{selectedOrganization?.name}</strong>?
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <div className="rounded-lg border bg-muted/30 p-4 text-sm text-muted-foreground">
                        The new organization becomes the resident&apos;s current home
                        organization. Previous home organization history remains in the
                        record.
                    </div>

                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => setConfirmOpen(false)}>
                            Back
                        </AlertDialogCancel>
                        <AlertDialogAction onClick={handleTransfer}>
                            Confirm Transfer
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
