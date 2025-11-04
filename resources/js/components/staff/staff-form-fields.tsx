import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';

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
                <div className="max-h-48 space-y-2 overflow-y-auto rounded-md border p-4">
                    {organizations.map((org) => (
                        <div
                            key={org.id}
                            className="flex items-center space-x-2"
                        >
                            <Checkbox
                                id={`org-${org.id}`}
                                name="organizations[]"
                                value={org.id}
                                defaultChecked={
                                    defaultValues.organizations?.some(
                                        (o: any) => o.id === org.id,
                                    ) || false
                                }
                            />
                            <Label
                                htmlFor={`org-${org.id}`}
                                className="cursor-pointer font-normal"
                            >
                                {org.name}
                            </Label>
                        </div>
                    ))}
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
                    defaultValue={
                        defaultValues.current_organization_id || ''
                    }
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option value="">No primary organization</option>
                    {organizations.map((org) => (
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
            </div>
        </>
    );
}

