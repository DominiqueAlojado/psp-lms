import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { useState, useEffect, useMemo } from 'react';

interface Role {
    id: number;
    name: string;
}

interface Organization {
    id: number;
    name: string;
}

interface StaffFormFieldsProps {
    defaultValues?: Record<string, any>;
    validationErrors?: Record<string, string>;
    roles: Role[];
    organizations: Organization[];
    isEdit?: boolean;
}

export function StaffFormFields({
    defaultValues = {},
    validationErrors = {},
    roles,
    organizations,
    isEdit = false,
}: StaffFormFieldsProps) {
    // Track selected organizations
    const [selectedOrgIds, setSelectedOrgIds] = useState<number[]>(
        defaultValues.organizations?.map((o: any) => o.id) || []
    );

    // Track current organization selection
    const [currentOrgId, setCurrentOrgId] = useState<number | null>(
        defaultValues.current_organization_id || null
    );
    const [orgSearch, setOrgSearch] = useState('');

    // Update current org if it's no longer in selected organizations
    useEffect(() => {
        if (currentOrgId && !selectedOrgIds.includes(currentOrgId)) {
            setCurrentOrgId(null);
        }
    }, [selectedOrgIds, currentOrgId]);

    const handleOrgCheckChange = (orgId: number, checked: boolean) => {
        if (checked) {
            setSelectedOrgIds([...selectedOrgIds, orgId]);
        } else {
            setSelectedOrgIds(selectedOrgIds.filter(id => id !== orgId));
        }
    };

    // Get available organizations for primary dropdown
    const availablePrimaryOrgs = organizations.filter(org => 
        selectedOrgIds.includes(org.id)
    );

    const filteredOrganizations = useMemo(() => {
        if (!orgSearch.trim()) {
            return organizations;
        }

        const query = orgSearch.toLowerCase();
        return organizations.filter((org) =>
            org.name.toLowerCase().includes(query),
        );
    }, [orgSearch, organizations]);

    return (
        <>
            <div className="space-y-1">
                <Label htmlFor="name">
                    Full Name <span className="text-destructive">*</span>
                </Label>
                <Input
                    id="name"
                    name="name"
                    type="text"
                    defaultValue={defaultValues.name || ''}
                    placeholder="e.g., Dr. Juan dela Cruz"
                />
                {validationErrors.name && (
                    <p className="text-sm text-destructive">
                        {validationErrors.name}
                    </p>
                )}
            </div>

            <div className="space-y-1">
                <Label htmlFor="email">
                    Email Address <span className="text-destructive">*</span>
                </Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    defaultValue={defaultValues.email || ''}
                    placeholder="e.g., juan.delacruz@hospital.com"
                />
                {validationErrors.email && (
                    <p className="text-sm text-destructive">
                        {validationErrors.email}
                    </p>
                )}
            </div>

            <div className="space-y-1">
                <Label htmlFor="password">
                    Password {isEdit && '(Leave blank to keep current)'}
                    {!isEdit && <span className="text-destructive">*</span>}
                </Label>
                <Input
                    id="password"
                    name="password"
                    type="password"
                    placeholder={
                        isEdit ? 'Leave blank to keep current' : 'Minimum 8 characters'
                    }
                />
                {validationErrors.password && (
                    <p className="text-sm text-destructive">
                        {validationErrors.password}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label>
                    Roles <span className="text-destructive">*</span>
                </Label>
                <div className="space-y-2 rounded-md border p-4">
                    {roles.map((role) => (
                        <div
                            key={role.id}
                            className="flex items-center space-x-2"
                        >
                            <Checkbox
                                id={`role-${role.id}`}
                                name="roles[]"
                                value={role.id}
                                defaultChecked={
                                    defaultValues.roles?.includes(role.id) ||
                                    false
                                }
                            />
                            <Label
                                htmlFor={`role-${role.id}`}
                                className="cursor-pointer font-normal"
                            >
                                {role.name}
                            </Label>
                        </div>
                    ))}
                </div>
                {validationErrors.roles && (
                    <p className="text-sm text-destructive">
                        {validationErrors.roles}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label>Organizations (Optional)</Label>
                <Input
                    type="text"
                    placeholder="Search organizations..."
                    value={orgSearch}
                    onChange={(e) => setOrgSearch(e.target.value)}
                    className="mb-3"
                />
                <div className="max-h-48 space-y-2 overflow-y-auto rounded-md border p-4">
                    {filteredOrganizations.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No organizations match your search.
                        </p>
                    ) : (
                        filteredOrganizations.map((org) => (
                            <div
                                key={org.id}
                                className="flex items-center space-x-2"
                            >
                                <Checkbox
                                    id={`org-${org.id}`}
                                    name="organizations[]"
                                    value={org.id}
                                    checked={selectedOrgIds.includes(org.id)}
                                    onCheckedChange={(checked) =>
                                        handleOrgCheckChange(org.id, checked as boolean)
                                    }
                                />
                                <Label
                                    htmlFor={`org-${org.id}`}
                                    className="cursor-pointer font-normal"
                                >
                                    {org.name}
                                </Label>
                            </div>
                        ))
                    )}
                </div>
                <p className="text-xs text-muted-foreground">
                    Select organizations this staff member can access
                </p>
            </div>

            <div className="space-y-1">
                <Label htmlFor="current_organization_id">
                    Current/Primary Organization
                </Label>
                <select
                    id="current_organization_id"
                    name="current_organization_id"
                    value={currentOrgId || ''}
                    onChange={(e) => setCurrentOrgId(e.target.value ? parseInt(e.target.value) : null)}
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled={selectedOrgIds.length === 0}
                >
                    <option value="">
                        {selectedOrgIds.length === 0 
                            ? 'Select organizations first' 
                            : 'No primary organization'}
                    </option>
                    {availablePrimaryOrgs.map((org) => (
                        <option key={org.id} value={org.id}>
                            {org.name}
                        </option>
                    ))}
                </select>
                {validationErrors.current_organization_id && (
                    <p className="text-sm text-destructive">
                        {validationErrors.current_organization_id}
                    </p>
                )}
                {selectedOrgIds.length === 0 && (
                    <p className="text-xs text-muted-foreground">
                        Select at least one organization above to set a primary organization
                    </p>
                )}
            </div>
        </>
    );
}

