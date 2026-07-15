import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import { EventFormFields } from './event-form-fields';

interface Props {
    open: boolean;
    onClose: () => void;
}

export function CreateEventSheet({ open, onClose }: Props) {
    const { errors: serverErrors, canCreateSystem } = usePage<{
        errors: Record<string, string>;
        canCreateSystem?: boolean;
    }>().props;
    const [data, setData] = useState<{
        title: string;
        scope: 'organization' | 'system';
        description: string;
        event_category: string;
        event_type: string;
        start_date: string;
        end_date: string;
        registration_deadline: string;
        location: string;
        virtual_link: string;
        capacity: string;
        price: string;
        is_free: boolean;
        cme_credits: string;
        requirements: string;
        requires_approval: boolean;
        is_published: boolean;
        image?: File | null;
    }>({
        title: '',
        scope: 'organization',
        description: '',
        event_category: 'other',
        event_type: 'in-person',
        start_date: '',
        end_date: '',
        registration_deadline: '',
        location: '',
        virtual_link: '',
        capacity: '',
        price: '0',
        is_free: true,
        cme_credits: '',
        requirements: '',
        requires_approval: false,
        is_published: false,
        image: null,
    });

    const [processing, setProcessing] = useState(false);

    const handleClose = () => {
        setData({
            title: '',
            scope: 'organization',
            description: '',
            event_category: 'other',
            event_type: 'in-person',
            start_date: '',
            end_date: '',
            registration_deadline: '',
            location: '',
            virtual_link: '',
            capacity: '',
            price: '0',
            is_free: true,
            cme_credits: '',
            requirements: '',
            requires_approval: false,
            is_published: false,
            image: null,
        });
        onClose();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        // Use FormData for file upload
        const formData = new FormData();
        Object.entries(data).forEach(([key, value]) => {
            if (key === 'image' && value instanceof File) {
                formData.append(key, value);
            } else if (value !== null && value !== undefined) {
                // Convert booleans to '1' or '0' for Laravel
                if (typeof value === 'boolean') {
                    formData.append(key, value ? '1' : '0');
                } else {
                    formData.append(key, value.toString());
                }
            }
        });

        router.post('/events', formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                handleClose();
            },
            onError: (errors) => {
                console.error('Validation errors:', errors);
                const errorMessages = Object.values(errors).flat();
                if (errorMessages.length > 0) {
                    toast.error(errorMessages[0] as string);
                }
                setProcessing(false);
            },
            onFinish: () => {
                setProcessing(false);
            },
        });
    };

    return (
        <Sheet open={open} onOpenChange={(isOpen) => !isOpen && handleClose()}>
            <SheetContent className="overflow-y-auto sm:max-w-2xl">
                <SheetHeader>
                    <SheetTitle>Create Event</SheetTitle>
                    <SheetDescription>
                        Add a new event or convention for residents
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={handleSubmit} className="p-4">
                    <EventFormFields
                        data={data}
                        setData={setData}
                        errors={serverErrors}
                        canCreateSystem={canCreateSystem}
                    />

                    <div className="flex justify-end gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleClose}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Event'}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>
    );
}
