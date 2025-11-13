import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { EventFormFields } from './event-form-fields';

interface Event {
    id: number;
    title: string;
    description: string | null;
    event_category: string;
    event_type: string;
    start_date: string;
    end_date: string;
    registration_deadline: string | null;
    location: string | null;
    virtual_link: string | null;
    capacity: number | null;
    price: string | null;
    is_free: boolean;
    image_path: string | null;
    cme_credits: string | null;
    requirements: string | null;
    requires_approval: boolean;
    is_published: boolean;
}

interface Props {
    open: boolean;
    onClose: () => void;
    event: Event | null;
}

export function EditEventSheet({ open, onClose, event }: Props) {
    const [data, setData] = useState<{
        title: string;
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
        existing_image?: string | null;
    }>({
        title: '',
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
        existing_image: null,
    });

    const [processing, setProcessing] = useState(false);
    const { errors: serverErrors } = usePage<{
        errors: Record<string, string>;
    }>().props;

    useEffect(() => {
        if (event) {
            setData({
                title: event.title || '',
                description: event.description || '',
                event_category: event.event_category || 'other',
                event_type: event.event_type || 'in-person',
                start_date: event.start_date?.slice(0, 16) || '',
                end_date: event.end_date?.slice(0, 16) || '',
                registration_deadline:
                    event.registration_deadline?.slice(0, 16) || '',
                location: event.location || '',
                virtual_link: event.virtual_link || '',
                capacity: event.capacity?.toString() || '',
                price: event.price || '0',
                is_free: event.is_free ?? true,
                cme_credits: event.cme_credits || '',
                requirements: event.requirements || '',
                requires_approval: event.requires_approval || false,
                is_published: event.is_published || false,
                image: null,
                existing_image: event.image_path
                    ? `/storage/${event.image_path}`
                    : null,
            });
        }
    }, [event]);

    const handleClose = () => {
        onClose();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!event) return;

        setProcessing(true);

        // Use FormData for file upload
        const formData = new FormData();
        Object.entries(data).forEach(([key, value]) => {
            if (key === 'image' && value instanceof File) {
                formData.append(key, value);
            } else if (
                key !== 'image' &&
                key !== 'existing_image' &&
                value !== null &&
                value !== undefined
            ) {
                // Convert booleans to '1' or '0' for Laravel
                if (typeof value === 'boolean') {
                    formData.append(key, value ? '1' : '0');
                } else {
                    formData.append(key, value.toString());
                }
            }
        });

        // Add _method for PATCH request when using FormData
        formData.append('_method', 'PATCH');

        router.post(`/events/${event.id}`, formData, {
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

    if (!event) return null;

    return (
        <Sheet open={open} onOpenChange={(isOpen) => !isOpen && handleClose()}>
            <SheetContent className="overflow-y-auto sm:max-w-2xl">
                <SheetHeader>
                    <SheetTitle>Edit Event</SheetTitle>
                    <SheetDescription>
                        Update event details and settings
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={handleSubmit} className="p-4">
                    <EventFormFields
                        data={data}
                        setData={setData}
                        errors={serverErrors}
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
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>
    );
}
