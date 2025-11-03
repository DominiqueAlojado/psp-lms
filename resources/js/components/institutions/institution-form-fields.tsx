import { Input } from '@/components/ui/input';
import { Label} from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { TrainingOfficersField } from './training-officers-field';

interface InstitutionFormFieldsProps {
    defaultValues?: Record<string, string | boolean | any[]>;
    validationErrors?: Record<string, string>;
}

export function InstitutionFormFields({
    defaultValues = {},
    validationErrors = {},
}: InstitutionFormFieldsProps) {
    return (
        <>
            <div className="space-y-2">
                <Label htmlFor="name">Institution Name</Label>
                <Input
                    id="name"
                    name="name"
                    defaultValue={(defaultValues.name as string) || ''}
                    placeholder="e.g., ABC Medical Center"
                />
                {validationErrors.name && (
                    <p className="text-sm text-destructive">
                        {validationErrors.name}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="type">Type</Label>
                <select
                    id="type"
                    name="type"
                    defaultValue={(defaultValues.type as string) || ''}
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <option value="">Select type...</option>
                    <option value="chapter">Chapter</option>
                    <option value="institution">Institution</option>
                    <option value="main">Main</option>
                    <option value="national">National</option>
                </select>
                {validationErrors.type && (
                    <p className="text-sm text-destructive">
                        {validationErrors.type}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                    id="description"
                    name="description"
                    defaultValue={(defaultValues.description as string) || ''}
                    placeholder="Brief description of the institution..."
                    rows={4}
                />
                {validationErrors.description && (
                    <p className="text-sm text-destructive">
                        {validationErrors.description}
                    </p>
                )}
            </div>

            <div className="flex items-center space-x-2">
                <input
                    type="checkbox"
                    id="is_active"
                    name="is_active"
                    defaultChecked={(defaultValues.is_active as boolean) ?? true}
                    className="h-4 w-4 rounded border-gray-300"
                />
                <Label htmlFor="is_active" className="cursor-pointer">
                    Active (Institution is currently operational)
                </Label>
            </div>

            <TrainingOfficersField
                defaultOfficers={(defaultValues.training_officers as any[]) || []}
                validationErrors={validationErrors}
            />
        </>
    );
}

