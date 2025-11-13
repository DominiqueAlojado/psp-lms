import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { usePermissions } from '@/hooks/use-permissions';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Clock, MapPin, Search, Settings, Users, Video } from 'lucide-react';
import { useState } from 'react';
import { format, parseISO } from 'date-fns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Events',
        href: '/events',
    },
];

interface Event {
    id: number;
    title: string;
    slug: string;
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
    is_published: boolean;
    organization: {
        id: number;
        name: string;
    } | null;
    creator: {
        id: number;
        name: string;
    };
    user_registration?: {
        id: number;
        registration_status: string;
    } | null;
}

interface PaginatedEvents {
    data: Event[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface PageProps {
    events: PaginatedEvents;
    filters: {
        category?: string;
        type?: string;
        filter?: string;
        search?: string;
    };
}

const eventCategories = [
    { value: 'convention', label: 'Convention' },
    { value: 'workshop', label: 'Workshop' },
    { value: 'seminar', label: 'Seminar' },
    { value: 'cme', label: 'CME' },
    { value: 'conference', label: 'Conference' },
    { value: 'symposium', label: 'Symposium' },
    { value: 'training', label: 'Training' },
    { value: 'other', label: 'Other' },
];

const eventTypes = [
    { value: 'in-person', label: 'In-Person' },
    { value: 'virtual', label: 'Virtual' },
    { value: 'hybrid', label: 'Hybrid' },
];

const eventTypeIcons = {
    'in-person': MapPin,
    virtual: Video,
    hybrid: Users,
};

const registrationStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    confirmed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    waitlisted: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
};

export default function EventsIndex({ events, filters }: PageProps) {
    const { hasPermission } = usePermissions();
    const canManage = hasPermission('view-events');
    const [searchQuery, setSearchQuery] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/events',
            { ...filters, search: searchQuery },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get(
            '/events',
            { ...filters, [key]: value },
            { preserveState: true, preserveScroll: true }
        );
    };

    const clearFilters = () => {
        setSearchQuery('');
        router.get('/events', {}, { preserveState: true, preserveScroll: true });
    };

    const formatDate = (dateString: string) => {
        try {
            return format(parseISO(dateString), 'MMM dd, yyyy');
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Events" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Events & Conventions"
                        description="Browse and register for upcoming events, conventions, and conferences"
                    />
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href="/events/my-registrations">
                                My Registrations
                            </Link>
                        </Button>
                        {canManage && (
                            <Button asChild>
                                <Link href="/events/manage">
                                    <Settings className="mr-2 h-4 w-4" />
                                    Manage Events
                                </Link>
                            </Button>
                        )}
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
                                    placeholder="Search events..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                            <Select value={filters.category} onValueChange={(value) => handleFilterChange('category', value)}>
                                <SelectTrigger className="w-full md:w-[180px]">
                                    <SelectValue placeholder="All Categories" />
                                </SelectTrigger>
                                <SelectContent>
                                    {eventCategories.map((cat) => (
                                        <SelectItem key={cat.value} value={cat.value}>
                                            {cat.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={filters.type} onValueChange={(value) => handleFilterChange('type', value)}>
                                <SelectTrigger className="w-full md:w-[180px]">
                                    <SelectValue placeholder="All Types" />
                                </SelectTrigger>
                                <SelectContent>
                                    {eventTypes.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={filters.filter} onValueChange={(value) => handleFilterChange('filter', value)}>
                                <SelectTrigger className="w-full md:w-[180px]">
                                    <SelectValue placeholder="All Events" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="upcoming">Upcoming</SelectItem>
                                    <SelectItem value="past">Past</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="submit">
                                Search
                            </Button>
                            {(filters.search || filters.category || filters.type || filters.filter) && (
                                <Button type="button" variant="outline" onClick={clearFilters}>
                                    Clear
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Events Grid */}
                {events.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Calendar className="mb-4 h-12 w-12 text-muted-foreground" />
                            <p className="text-lg font-medium">No events found</p>
                            <p className="text-muted-foreground">
                                {filters.search || filters.category || filters.type || filters.filter
                                    ? 'Try adjusting your filters'
                                    : 'Check back later for upcoming events'}
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {events.data.map((event) => {
                                const TypeIcon = eventTypeIcons[event.event_type];
                                const isRegistered = !!event.user_registration;
                                const registrationStatus = event.user_registration?.registration_status;

                                return (
                                    <Card key={event.id} className="flex flex-col overflow-hidden">
                                        {event.image_path && (
                                            <div className="aspect-video w-full overflow-hidden">
                                                <img
                                                    src={`/storage/${event.image_path}`}
                                                    alt={event.title}
                                                    className="h-full w-full object-cover transition-transform hover:scale-105"
                                                />
                                            </div>
                                        )}
                                        <CardHeader>
                                            <div className="mb-2 flex items-start justify-between gap-2">
                                                <Badge variant="outline" className="capitalize">
                                                    {event.event_category.replace('-', ' ')}
                                                </Badge>
                                                <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                                    <TypeIcon className="h-4 w-4" />
                                                    <span className="capitalize">{event.event_type.replace('-', ' ')}</span>
                                                </div>
                                            </div>
                                            <CardTitle className="line-clamp-2">{event.title}</CardTitle>
                                            <CardDescription className="line-clamp-3">
                                                {event.description 
                                                    ? event.description.replace(/<[^>]*>/g, '') 
                                                    : 'No description available'}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="flex-1">
                                            <div className="space-y-2 text-sm">
                                                <div className="flex items-center gap-2 text-muted-foreground">
                                                    <Calendar className="h-4 w-4" />
                                                    <span>{formatDate(event.start_date)}</span>
                                                </div>
                                                {!event.is_free && event.price && (
                                                    <div className="flex items-center gap-2 font-medium text-primary">
                                                        <span>₱{parseFloat(event.price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                                    </div>
                                                )}
                                                {event.is_free && (
                                                    <div className="flex items-center gap-2 font-medium text-green-600 dark:text-green-400">
                                                        <span>Free Event</span>
                                                    </div>
                                                )}
                                                {event.location && (
                                                    <div className="flex items-center gap-2 text-muted-foreground">
                                                        <MapPin className="h-4 w-4" />
                                                        <span className="line-clamp-1">{event.location}</span>
                                                    </div>
                                                )}
                                                {event.cme_credits && (
                                                    <div className="flex items-center gap-2 text-muted-foreground">
                                                        <Clock className="h-4 w-4" />
                                                        <span>{event.cme_credits} CME Credits</span>
                                                    </div>
                                                )}
                                                {isRegistered && registrationStatus && (
                                                    <div className="mt-3">
                                                        <Badge className={registrationStatusColors[registrationStatus as keyof typeof registrationStatusColors]}>
                                                            {registrationStatus.charAt(0).toUpperCase() + registrationStatus.slice(1)}
                                                        </Badge>
                                                    </div>
                                                )}
                                            </div>
                                        </CardContent>
                                        <CardFooter>
                                            <Button asChild className="w-full">
                                                <Link href={`/events/${event.id}`}>
                                                    View Details
                                                </Link>
                                            </Button>
                                        </CardFooter>
                                    </Card>
                                );
                            })}
                        </div>

                        {/* Pagination */}
                        {events.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
                                {Array.from({ length: events.last_page }, (_, i) => i + 1).map((page) => (
                                    <Button
                                        key={page}
                                        variant={page === events.current_page ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                '/events',
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

