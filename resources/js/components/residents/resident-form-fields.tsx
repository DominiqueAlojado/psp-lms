import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Organization {
    id: number;
    name: string;
}

interface PersonalFieldsProps {
    organizations: Organization[];
    yearLevels: string[];
    statuses: string[];
    defaultValues?: Record<string, string>;
    validationErrors?: Record<string, string>;
    showOrganization?: boolean;
}

export function PersonalInformationFields({
    organizations,
    yearLevels,
    statuses,
    defaultValues = {},
    validationErrors = {},
    showOrganization = true,
}: PersonalFieldsProps) {
    return (
        <>
            {showOrganization && (
                <div className="space-y-2">
                    <Label htmlFor="organization_id">Organization</Label>
                    <select
                        id="organization_id"
                        name="organization_id"
                        defaultValue={defaultValues.organization_id || ''}
                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">Select organization...</option>
                        {organizations.map((org) => (
                            <option key={org.id} value={org.id}>
                                {org.name}
                            </option>
                        ))}
                    </select>
                    {validationErrors.organization_id && (
                        <p className="text-sm text-destructive">
                            {validationErrors.organization_id}
                        </p>
                    )}
                </div>
            )}

            <div className="space-y-2">
                <Label htmlFor="first_name">First Name</Label>
                <Input
                    id="first_name"
                    name="first_name"
                    defaultValue={defaultValues.first_name || ''}
                />
                {validationErrors.first_name && (
                    <p className="text-sm text-destructive">
                        {validationErrors.first_name}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="middle_name">Middle Name</Label>
                <Input
                    id="middle_name"
                    name="middle_name"
                    defaultValue={defaultValues.middle_name || ''}
                />
                {validationErrors.middle_name && (
                    <p className="text-sm text-destructive">
                        {validationErrors.middle_name}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="last_name">Last Name</Label>
                <Input
                    id="last_name"
                    name="last_name"
                    defaultValue={defaultValues.last_name || ''}
                />
                {validationErrors.last_name && (
                    <p className="text-sm text-destructive">
                        {validationErrors.last_name}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                    id="email"
                    name="email"
                    defaultValue={defaultValues.email || ''}
                />
                {validationErrors.email && (
                    <p className="text-sm text-destructive">
                        {validationErrors.email}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="contact_number">Contact Number</Label>
                <Input
                    id="contact_number"
                    name="contact_number"
                    type="tel"
                    placeholder="09123456789 or +639123456789"
                    defaultValue={defaultValues.contact_number || ''}
                    onInput={(e) => {
                        const input = e.currentTarget;
                        input.value = input.value.replace(/[^\d+]/g, '');
                    }}
                />
                <p className="text-xs text-muted-foreground">
                    Philippine mobile number format (11 digits)
                </p>
                {validationErrors.contact_number && (
                    <p className="text-sm text-destructive">
                        {validationErrors.contact_number}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="course">Course</Label>
                <Input
                    id="course"
                    name="course"
                    defaultValue={defaultValues.course || ''}
                />
                {validationErrors.course && (
                    <p className="text-sm text-destructive">
                        {validationErrors.course}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="year_level">Year Level</Label>
                <select
                    id="year_level"
                    name="year_level"
                    defaultValue={defaultValues.year_level || ''}
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">Select year level...</option>
                    {yearLevels.map((level) => (
                        <option key={level} value={level}>
                            {level}
                        </option>
                    ))}
                </select>
                {validationErrors.year_level && (
                    <p className="text-sm text-destructive">
                        {validationErrors.year_level}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="status">Status</Label>
                <select
                    id="status"
                    name="status"
                    defaultValue={defaultValues.status || 'active'}
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    {statuses.map((status) => (
                        <option key={status} value={status}>
                            {status.charAt(0).toUpperCase() + status.slice(1)}
                        </option>
                    ))}
                </select>
                {validationErrors.status && (
                    <p className="text-sm text-destructive">
                        {validationErrors.status}
                    </p>
                )}
            </div>
        </>
    );
}

interface AccountFieldsProps {
    defaultValues?: Record<string, string>;
    validationErrors?: Record<string, string>;
    isOptional?: boolean;
}

export function AccountInformationFields({
    defaultValues = {},
    validationErrors = {},
    isOptional = false,
}: AccountFieldsProps) {
    return (
        <>
            <div className="space-y-2">
                <Label htmlFor="password">
                    {isOptional ? 'New Password' : 'Password'}
                </Label>
                <Input
                    id="password"
                    name="password"
                    type="password"
                    placeholder={
                        isOptional
                            ? 'Leave blank to keep current password'
                            : undefined
                    }
                    defaultValue={defaultValues.password || ''}
                />
                {validationErrors.password && (
                    <p className="text-sm text-destructive">
                        {validationErrors.password}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="password_confirmation">Confirm Password</Label>
                <Input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    placeholder={
                        isOptional ? 'Confirm new password' : 'Confirm password'
                    }
                    defaultValue={defaultValues.password_confirmation || ''}
                />
                {validationErrors.password_confirmation && (
                    <p className="text-sm text-destructive">
                        {validationErrors.password_confirmation}
                    </p>
                )}
            </div>
        </>
    );
}
