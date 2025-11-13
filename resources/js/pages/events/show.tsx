import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertCircle, Calendar, Clock, ExternalLink, MapPin, Users, Video, X } from 'lucide-react';
import { format, parseISO } from 'date-fns';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useState } from 'react';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';

interface Event {
    id: number;
    title: string;
    description: string | null;
    event_category: string;
    event_type: 'in-person' | 'virtual' | 'hybrid';
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
    organization: {
        id: number;
        name: string;
    } | null;
    creator: {
        id: number;
        name: string;
    };
    speakers: Array<{
        name: string;
        title?: string;
        bio?: string;
        organization?: string;
        email?: string;
        photo_path?: string;
    }> | null;
    agenda_items: Array<{
        day: number;
        start_time: string;
        end_time: string;
        session_title: string;
        description?: string;
        speaker?: string;
        location?: string;
    }> | null;
}

interface UserRegistration {
    id: number;
    registration_status: string;
    created_at: string;
}

interface RegistrationStats {
    total: number;
    capacity: number | null;
    remaining: number | null;
}

interface PageProps {
    event: Event;
    userRegistration: UserRegistration | null;
    registrationStats: RegistrationStats;
    canRegister: boolean;
}

const registrationStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    confirmed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    waitlisted: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
};

export default function EventShow({ event, userRegistration, registrationStats, canRegister }: PageProps) {
    const [showCancelDialog, setShowCancelDialog] = useState(false);
    const cancelForm = useForm({
        reason: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Events', href: '/events' },
        { title: event.title, href: `/events/${event.id}` },
    ];

    const formatDate = (dateString: string) => {
        try {
            return format(parseISO(dateString), 'EEEE, MMMM dd, yyyy');
        } catch {
            return dateString;
        }
    };

    const formatDateTime = (dateString: string) => {
        try {
            return format(parseISO(dateString), 'MMM dd, yyyy h:mm a');
        } catch {
            return dateString;
        }
    };

    const handleRegister = () => {
        router.post(`/events/${event.id}/register`, {}, {
            preserveScroll: true,
        });
    };

    const handleCancelRegistration = () => {
        cancelForm.post(`/events/${event.id}/cancel-registration`, {
            preserveScroll: true,
            onSuccess: () => {
                setShowCancelDialog(false);
                cancelForm.reset();
            },
        });
    };

    const isRegistered = !!userRegistration;
    const canCancel = isRegistered && ['pending', 'confirmed', 'approved', 'waitlisted'].includes(userRegistration.registration_status);

    // Group agenda items by day
    const agendaByDay = event.agenda_items?.reduce((acc, item) => {
        if (!acc[item.day]) {
            acc[item.day] = [];
        }
        acc[item.day].push(item);
        return acc;
    }, {} as Record<number, typeof event.agenda_items>) || {};

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={event.title} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                {/* Event Banner */}
                {event.image_path && (
                    <div className="relative aspect-[21/9] w-full overflow-hidden rounded-xl">
                        <img
                            src={`/storage/${event.image_path}`}
                            alt={event.title}
                            className="h-full w-full object-cover"
                        />
                    </div>
                )}

                <div className="flex items-start justify-between gap-4">
                    <div className="flex-1">
                        <div className="mb-2 flex flex-wrap items-center gap-2">
                            <Badge variant="outline" className="capitalize">
                                {event.event_category.replace('-', ' ')}
                            </Badge>
                            <Badge variant="secondary" className="capitalize">
                                {event.event_type.replace('-', ' ')}
                            </Badge>
                            {event.cme_credits && (
                                <Badge variant="default">
                                    {event.cme_credits} CME Credits
                                </Badge>
                            )}
                        </div>
                        <HeadingSmall
                            title={event.title}
                            description={event.organization?.name || 'System-wide Event'}
                        />
                    </div>
                    <Button asChild variant="outline">
                        <Link href="/events">
                            Back to Events
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Main Content */}
                    <div className="space-y-6 lg:col-span-2">
                        {/* Description */}
                        <Card>
                            <CardHeader>
                                <CardTitle>About This Event</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {event.description ? (
                                    <div
                                        className="prose prose-sm max-w-none dark:prose-invert [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:space-y-1 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:space-y-1 [&_li]:text-muted-foreground [&_strong]:font-semibold [&_strong]:text-foreground [&_a]:text-primary [&_a]:underline [&_em]:italic"
                                        dangerouslySetInnerHTML={{ __html: event.description }}
                                    />
                                ) : (
                                    <p className="text-muted-foreground">No description available.</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Requirements */}
                        {event.requirements && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Requirements</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div
                                        className="prose prose-sm max-w-none dark:prose-invert [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:space-y-1 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:space-y-1 [&_li]:text-muted-foreground [&_strong]:font-semibold [&_strong]:text-foreground [&_a]:text-primary [&_a]:underline [&_em]:italic"
                                        dangerouslySetInnerHTML={{ __html: event.requirements }}
                                    />
                                </CardContent>
                            </Card>
                        )}

                        {/* Speakers */}
                        {event.speakers && event.speakers.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Speakers</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {event.speakers.map((speaker, index) => (
                                            <div key={index} className="flex flex-col gap-2">
                                                <div>
                                                    <h4 className="font-semibold">{speaker.name}</h4>
                                                    {speaker.title && (
                                                        <p className="text-sm text-muted-foreground">{speaker.title}</p>
                                                    )}
                                                    {speaker.organization && (
                                                        <p className="text-sm text-muted-foreground">{speaker.organization}</p>
                                                    )}
                                                </div>
                                                {speaker.bio && (
                                                    <p className="text-sm text-muted-foreground">{speaker.bio}</p>
                                                )}
                                                {index < event.speakers.length - 1 && (
                                                    <Separator className="mt-2" />
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Agenda */}
                        {Object.keys(agendaByDay).length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Agenda</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-6">
                                        {Object.entries(agendaByDay).map(([day, items]) => (
                                            <div key={day}>
                                                <h4 className="mb-3 font-semibold">Day {day}</h4>
                                                <div className="space-y-3">
                                                    {items.map((item, index) => (
                                                        <div key={index} className="flex gap-4 rounded-lg border p-3">
                                                            <div className="flex-shrink-0 text-sm font-medium text-muted-foreground">
                                                                {item.start_time} - {item.end_time}
                                                            </div>
                                                            <div className="flex-1">
                                                                <h5 className="font-medium">{item.session_title}</h5>
                                                                {item.description && (
                                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                                        {item.description}
                                                                    </p>
                                                                )}
                                                                <div className="mt-2 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                                                    {item.speaker && (
                                                                        <span>Speaker: {item.speaker}</span>
                                                                    )}
                                                                    {item.location && (
                                                                        <span>Location: {item.location}</span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Registration Status / Action */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Registration</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {!event.is_free && event.price && (
                                    <div className="rounded-lg bg-primary/10 p-3">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-medium">Registration Fee:</span>
                                            <span className="text-lg font-bold">₱{parseFloat(event.price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                        </div>
                                    </div>
                                )}
                                {isRegistered ? (
                                    <>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-muted-foreground">Status:</span>
                                            <Badge className={registrationStatusColors[userRegistration.registration_status as keyof typeof registrationStatusColors]}>
                                                {userRegistration.registration_status.charAt(0).toUpperCase() + userRegistration.registration_status.slice(1)}
                                            </Badge>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-muted-foreground">Registered:</span>
                                            <span className="text-sm">{formatDateTime(userRegistration.created_at)}</span>
                                        </div>
                                        {userRegistration.registration_status === 'pending' && (
                                            <div className="rounded-md bg-yellow-50 p-3 dark:bg-yellow-900/20">
                                                <p className="text-sm text-yellow-800 dark:text-yellow-400">
                                                    Your registration is pending approval from organizers.
                                                </p>
                                            </div>
                                        )}
                                        {userRegistration.registration_status === 'waitlisted' && (
                                            <div className="rounded-md bg-gray-50 p-3 dark:bg-gray-900/20">
                                                <p className="text-sm text-gray-800 dark:text-gray-400">
                                                    You are on the waitlist. We'll notify you if a spot becomes available.
                                                </p>
                                            </div>
                                        )}
                                        {canCancel && (
                                            <Button 
                                                variant="destructive" 
                                                className="w-full"
                                                onClick={() => setShowCancelDialog(true)}
                                            >
                                                Cancel Registration
                                            </Button>
                                        )}
                                    </>
                                ) : (
                                    <>
                                        {canRegister ? (
                                            <>
                                                {event.requires_approval && (
                                                    <div className="rounded-md bg-blue-50 p-3 dark:bg-blue-900/20">
                                                        <p className="text-sm text-blue-800 dark:text-blue-400">
                                                            <AlertCircle className="mr-1 inline h-4 w-4" />
                                                            Registration requires approval
                                                        </p>
                                                    </div>
                                                )}
                                                <Button className="w-full" onClick={handleRegister}>
                                                    Register Now
                                                </Button>
                                            </>
                                        ) : (
                                            <div className="rounded-md bg-red-50 p-3 dark:bg-red-900/20">
                                                <p className="text-sm text-red-800 dark:text-red-400">
                                                    Registration is closed
                                                </p>
                                            </div>
                                        )}
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Event Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Event Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex items-start gap-3">
                                    <Calendar className="mt-0.5 h-5 w-5 flex-shrink-0 text-muted-foreground" />
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">Date</p>
                                        <p className="text-sm text-muted-foreground">
                                            {formatDate(event.start_date)}
                                            {event.start_date !== event.end_date && ` - ${formatDate(event.end_date)}`}
                                        </p>
                                    </div>
                                </div>

                                {event.location && (
                                    <div className="flex items-start gap-3">
                                        <MapPin className="mt-0.5 h-5 w-5 flex-shrink-0 text-muted-foreground" />
                                        <div className="flex-1">
                                            <p className="text-sm font-medium">Location</p>
                                            <p className="text-sm text-muted-foreground">{event.location}</p>
                                        </div>
                                    </div>
                                )}

                                {event.virtual_link && (event.event_type === 'virtual' || event.event_type === 'hybrid') && (
                                    <div className="flex items-start gap-3">
                                        <Video className="mt-0.5 h-5 w-5 flex-shrink-0 text-muted-foreground" />
                                        <div className="flex-1">
                                            <p className="text-sm font-medium">Virtual Link</p>
                                            <a 
                                                href={event.virtual_link} 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                className="flex items-center gap-1 text-sm text-primary hover:underline"
                                            >
                                                Join Online <ExternalLink className="h-3 w-3" />
                                            </a>
                                        </div>
                                    </div>
                                )}

                                {event.registration_deadline && (
                                    <div className="flex items-start gap-3">
                                        <Clock className="mt-0.5 h-5 w-5 flex-shrink-0 text-muted-foreground" />
                                        <div className="flex-1">
                                            <p className="text-sm font-medium">Registration Deadline</p>
                                            <p className="text-sm text-muted-foreground">
                                                {formatDateTime(event.registration_deadline)}
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {event.capacity && (
                                    <div className="flex items-start gap-3">
                                        <Users className="mt-0.5 h-5 w-5 flex-shrink-0 text-muted-foreground" />
                                        <div className="flex-1">
                                            <p className="text-sm font-medium">Capacity</p>
                                            <p className="text-sm text-muted-foreground">
                                                {registrationStats.total} / {registrationStats.capacity} registered
                                                {registrationStats.remaining !== null && registrationStats.remaining > 0 && (
                                                    <span className="ml-1">({registrationStats.remaining} spots left)</span>
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Cancel Registration Dialog */}
            <Dialog open={showCancelDialog} onOpenChange={setShowCancelDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Cancel Registration</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to cancel your registration for this event?
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="reason">Reason (optional)</Label>
                            <Textarea
                                id="reason"
                                placeholder="Let us know why you're cancelling..."
                                value={cancelForm.data.reason}
                                onChange={(e) => cancelForm.setData('reason', e.target.value)}
                                rows={4}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowCancelDialog(false)}>
                            Keep Registration
                        </Button>
                        <Button 
                            variant="destructive" 
                            onClick={handleCancelRegistration}
                            disabled={cancelForm.processing}
                        >
                            {cancelForm.processing ? 'Cancelling...' : 'Cancel Registration'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

