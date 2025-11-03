import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface TrainingOfficer {
    name: string;
    email: string;
}

interface Props {
    defaultOfficers?: TrainingOfficer[];
    validationErrors?: Record<string, string>;
}

export function TrainingOfficersField({
    defaultOfficers = [],
    validationErrors = {},
}: Props) {
    const [officers, setOfficers] = useState<TrainingOfficer[]>(
        defaultOfficers.length > 0 ? defaultOfficers : [{ name: '', email: '' }],
    );

    const addOfficer = () => {
        setOfficers([...officers, { name: '', email: '' }]);
    };

    const removeOfficer = (index: number) => {
        if (officers.length === 1) {
            // Keep at least one empty field
            setOfficers([{ name: '', email: '' }]);
        } else {
            setOfficers(officers.filter((_, i) => i !== index));
        }
    };

    const updateOfficer = (index: number, field: 'name' | 'email', value: string) => {
        const updated = [...officers];
        updated[index][field] = value;
        setOfficers(updated);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <Label>Training Officers</Label>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addOfficer}
                >
                    <Plus className="mr-2 h-4 w-4" />
                    Add Officer
                </Button>
            </div>

            <div className="space-y-3">
                {officers.map((officer, index) => (
                    <div
                        key={index}
                        className="flex gap-2 rounded-lg border p-3"
                    >
                        <div className="flex-1 space-y-3">
                            <div className="space-y-1">
                                <Label
                                    htmlFor={`officer_name_${index}`}
                                    className="text-xs"
                                >
                                    Name
                                </Label>
                                <Input
                                    id={`officer_name_${index}`}
                                    name={`training_officers[${index}][name]`}
                                    value={officer.name}
                                    onChange={(e) =>
                                        updateOfficer(
                                            index,
                                            'name',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g., Dr. Juan dela Cruz"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label
                                    htmlFor={`officer_email_${index}`}
                                    className="text-xs"
                                >
                                    Email
                                </Label>
                                <Input
                                    id={`officer_email_${index}`}
                                    name={`training_officers[${index}][email]`}
                                    type="email"
                                    value={officer.email}
                                    onChange={(e) =>
                                        updateOfficer(
                                            index,
                                            'email',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g., juan.delacruz@hospital.com"
                                />
                            </div>
                        </div>

                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => removeOfficer(index)}
                            className="mt-6 h-8 w-8 p-0"
                        >
                            <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                ))}
            </div>

            {validationErrors.training_officers && (
                <p className="text-sm text-destructive">
                    {validationErrors.training_officers}
                </p>
            )}

            <p className="text-xs text-muted-foreground">
                Training officers are the primary contacts for this institution's
                training program.
            </p>
        </div>
    );
}

