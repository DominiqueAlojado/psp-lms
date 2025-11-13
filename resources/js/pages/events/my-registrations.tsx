import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, CalendarDays, MapPin } from 'lucide-react';
import { format, parseISO } from 'date-fns';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Events', href: '/events' },
    { title: 'My Registrations', href: '/events/my-registrations' },
];

interface EventRegistration {
    id: number;
    registration_status: string;
    payment_status: string;
    created_at: string;
    event: {
        id: number;
        title: string;
        event_category: string;
        event_type: string;
        start_date: string;
        end_date: string;
        location: string | null;
        organization: {
            id: number;
            name: string;
        } | null;
    };
}

interface PaginatedRegistrations {
    data: EventRegistration[];
    total: number;
    current_page: number;
    last_page: number;
}

interface PageProps {
    registrations: PaginatedRegistrations;
    filters: {
        status?: string;
    };
}

const registrationStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    confirmed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    waitlisted: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
};

export default function MyRegistrations({ registrations, filters }: PageProps) {
    const handleFilterChange = (value: string) => {
        router.get(
            '/events/my-registrations',
            { status: value },
            { preserveState: true, preserveScroll: true }
        );
    };

    const formatDate = (dateString: string) => {
        try {
            return format(parseISO(dateString), 'MMM dd, yyyy');
        } catch {
            return dateString;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Event Registrations" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="My Event Registrations"
                        description="View and manage your event registrations"
                    />
                    <Button asChild>
                        <Link href="/events">
                            Browse Events
                        </Link>
                    </Button>
                </div>

                {/* Filter */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-4">
                            <span className="text-sm font-medium">Filter by status:</span>
                            <Select value={filters.status} onValueChange={handleFilterChange}>
                                <SelectTrigger className="w-[200px]">
                                    <SelectValue placeholder="All Registrations" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="confirmed">Confirmed</SelectItem>
                                    <SelectItem value="approved">Approved</SelectItem>
                                    <SelectItem value="waitlisted">Waitlisted</SelectItem>
                                    <SelectItem value="cancelled">Cancelled</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {/* Registrations List */}
                {registrations.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <CalendarDays className="mb-4 h-12 w-12 text-muted-foreground" />
                            <p className="text-lg font-medium">No registrations found</p>
                            <p className="text-muted-foreground">
                                {filters.status
                                    ? 'Try adjusting your filter'
                                    : 'You haven\'t registered for any events yet'}
                            </p>
                            {!filters.status && (
                                <Button asChild className="mt-4">
                                    <Link href="/events">
                                        Browse Events
                                    </Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <div className="space-y-4">
                            {registrations.data.map((registration) => (
                                <Card key={registration.id}>
                                    <CardContent className="flex flex-col gap-4 pt-6 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="flex-1 space-y-3">
                                            <div>
                                                <div className="mb-2 flex flex-wrap items-center gap-2">
                                                    <Badge variant="outline" className="capitalize">
                                                        {registration.event.event_category.replace('-', ' ')}
                                                    </Badge>
                                                    <Badge className={registrationStatusColors[registration.registration_status as keyof typeof registrationStatusColors]}>
                                                        {registration.registration_status.charAt(0).toUpperCase() + registration.registration_status.slice(1)}
                                                    </Badge>
                                                </div>
                                                <h3 className="font-semibold">{registration.event.title}</h3>
                                                {registration.event.organization && (
                                                    <p className="text-sm text-muted-foreground">
                                                        {registration.event.organization.name}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                <div className="flex items-center gap-2">
                                                    <Calendar className="h-4 w-4" />
                                                    <span>{formatDate(registration.event.start_date)}</span>
                                                </div>
                                                {registration.event.location && (
                                                    <div className="flex items-center gap-2">
                                                        <MapPin className="h-4 w-4" />
                                                        <span>{registration.event.location}</span>
                                                    </div>
                                                )}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                Registered: {formatDate(registration.created_at)}
                                            </div>
                                        </div>
                                        <div className="flex flex-col gap-2 sm:flex-shrink-0">
                                            <Button asChild>
                                                <Link href={`/events/${registration.event.id}`}>
                                                    View Event
                                                </Link>
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {/* Pagination */}
                        {registrations.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
                                {Array.from({ length: registrations.last_page }, (_, i) => i + 1).map((page) => (
                                    <Button
                                        key={page}
                                        variant={page === registrations.current_page ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                '/events/my-registrations',
                                                { ...filters, page },
                                                { preserveState: true, preserveScroll: true }
                                            )
                                        }
                                    >
                                        {page}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}

