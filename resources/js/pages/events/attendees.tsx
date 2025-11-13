import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Check, Search, UserCheck } from 'lucide-react';
import { useState } from 'react';
import { format, parseISO } from 'date-fns';

interface Event {
    id: number;
    title: string;
}

interface Registration {
    id: number;
    registration_status: string;
    payment_status: string;
    payment_amount: string;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
        resident: {
            year_level: string;
        } | null;
    };
    organization: {
        id: number;
        name: string;
    };
}

interface PaginatedRegistrations {
    data: Registration[];
    total: number;
    current_page: number;
    last_page: number;
}

interface PageProps {
    event: Event;
    registrations: PaginatedRegistrations;
    filters: {
        status?: string;
        search?: string;
    };
}

const registrationStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    confirmed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    waitlisted: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
};

const paymentStatusColors = {
    not_required: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    paid: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    refunded: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

export default function EventAttendees({ event, registrations, filters }: PageProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Events', href: '/events' },
        { title: 'Manage', href: '/events/manage' },
        { title: event.title, href: `/events/${event.id}` },
        { title: 'Attendees', href: `/events/${event.id}/attendees` },
    ];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            `/events/${event.id}/attendees`,
            { ...filters, search: searchQuery },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleFilterChange = (value: string) => {
        router.get(
            `/events/${event.id}/attendees`,
            { ...filters, status: value },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleApprove = (registrationId: number) => {
        router.post(`/events/${event.id}/registrations/${registrationId}/approve`, {}, {
            preserveScroll: true,
        });
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
            <Head title={`${event.title} - Attendees`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Event Attendees"
                        description={`Manage registrations for ${event.title}`}
                    />
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href="/events/manage">Back to Manage</Link>
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <form onSubmit={handleSearch} className="flex flex-col gap-4 md:flex-row">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Search by name or email..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                            <Select value={filters.status} onValueChange={handleFilterChange}>
                                <SelectTrigger className="w-full md:w-[200px]">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="confirmed">Confirmed</SelectItem>
                                    <SelectItem value="approved">Approved</SelectItem>
                                    <SelectItem value="waitlisted">Waitlisted</SelectItem>
                                    <SelectItem value="cancelled">Cancelled</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="submit">Search</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Registrations List */}
                {registrations.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <UserCheck className="mb-4 h-12 w-12 text-muted-foreground" />
                            <p className="text-lg font-medium">No registrations found</p>
                            <p className="text-muted-foreground">
                                {filters.search || filters.status
                                    ? 'Try adjusting your filters'
                                    : 'No one has registered yet'}
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <Card>
                            <CardContent className="p-0">
                                <div className="divide-y">
                                    {registrations.data.map((registration) => (
                                        <div key={registration.id} className="flex items-center justify-between p-4">
                                            <div className="flex-1 space-y-2">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <h4 className="font-medium">{registration.user.name}</h4>
                                                    <Badge className={registrationStatusColors[registration.registration_status as keyof typeof registrationStatusColors]}>
                                                        {registration.registration_status}
                                                    </Badge>
                                                    {registration.payment_status !== 'not_required' && (
                                                        <Badge className={paymentStatusColors[registration.payment_status as keyof typeof paymentStatusColors]}>
                                                            {registration.payment_status === 'paid' ? 'Paid' : 
                                                             registration.payment_status === 'pending' ? 'Payment Pending' : 
                                                             registration.payment_status}
                                                        </Badge>
                                                    )}
                                                </div>
                                                <p className="text-sm text-muted-foreground">{registration.user.email}</p>
                                                <div className="flex flex-wrap items-center gap-3 text-sm">
                                                    <span className="font-medium text-primary">
                                                        {registration.organization.name}
                                                    </span>
                                                    {registration.user.resident && (
                                                        <span className="text-muted-foreground">{registration.user.resident.year_level}</span>
                                                    )}
                                                </div>
                                                <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                                    <span>Registered: {formatDate(registration.created_at)}</span>
                                                    {registration.payment_amount && parseFloat(registration.payment_amount) > 0 ? (
                                                        <span className="font-medium text-foreground">
                                                            Fee: ₱{parseFloat(registration.payment_amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                        </span>
                                                    ) : (
                                                        <span className="font-medium text-green-600 dark:text-green-400">Free</span>
                                                    )}
                                                </div>
                                            </div>
                                            {registration.registration_status === 'pending' && (
                                                <Button
                                                    size="sm"
                                                    onClick={() => handleApprove(registration.id)}
                                                >
                                                    <Check className="mr-2 h-4 w-4" />
                                                    Approve
                                                </Button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

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
                                                `/events/${event.id}/attendees`,
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

