import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { preserveOrgParam } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { usePermissions } from '@/hooks/use-permissions';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Calendar,
    Clock,
    MapPin,
    Search,
    Settings,
    Users,
    Video,
} from 'lucide-react';
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
    pending: 'border-transparent bg-warning-soft text-warning',
    approved: 'border-transparent bg-success-soft text-success',
    confirmed: 'border-transparent bg-success-soft text-success',
    cancelled: 'border-transparent bg-destructive/12 text-destructive',
    waitlisted: 'border-transparent bg-muted text-muted-foreground',
};

export default function EventsIndex({ events, filters }: PageProps) {
    const { hasPermission } = usePermissions();
    const { auth } = usePage<SharedData>().props;
    const currentOrgSlug = auth.currentOrganization?.slug;
    const canManage = hasPermission('view-events');
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const freeEventsCount = events.data.filter((event) => event.is_free).length;
    const registeredCount = events.data.filter(
        (event) => !!event.user_registration,
    ).length;
    const categoryCount = new Set(
        events.data.map((event) => event.event_category),
    ).size;

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            preserveOrgParam('/events', currentOrgSlug),
            { ...filters, search: searchQuery },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get(
            preserveOrgParam('/events', currentOrgSlug),
            { ...filters, [key]: value },
            { preserveState: true, preserveScroll: true }
        );
    };

    const clearFilters = () => {
        setSearchQuery('');
        router.get(
            preserveOrgParam('/events', currentOrgSlug),
            {},
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
            <Head title="Events" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <HeadingSmall
                        title="Events & Conventions"
                        description="Browse and register for upcoming events, conventions, and conferences"
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={preserveOrgParam('/events/my-registrations', currentOrgSlug)}>
                                My Registrations
                            </Link>
                        </Button>
                        {canManage && (
                            <Button asChild>
                                <Link href={preserveOrgParam('/events/manage', currentOrgSlug)}>
                                    <Settings className="mr-2 h-4 w-4" />
                                    Manage Events
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="All Events"
                        value={events.total}
                        description="Events matching the current feed"
                        icon={Calendar}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Registered"
                        value={registeredCount}
                        description="Visible events you already joined"
                        icon={Users}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Free Events"
                        value={freeEventsCount}
                        description="No-cost events on this page"
                        icon={Clock}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Categories"
                        value={categoryCount}
                        description="Event types currently represented"
                        icon={Settings}
                        iconColor="text-primary"
                    />
                </div>

                <Card className="overflow-hidden border-primary/10 bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-card)_96%,var(--color-primary)_4%),color-mix(in_oklab,var(--color-card)_92%,black))] shadow-[0_24px_60px_-36px_rgb(0_0_0_/_0.5)]">
                    <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Event hub
                            </p>
                            <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                Browse upcoming sessions and conventions
                            </h3>
                            <p className="text-sm leading-6 text-muted-foreground">
                                Explore resident events, track registrations, and quickly jump into the sessions that match your schedule.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge className="border-transparent bg-primary/14 text-primary">
                                {events.total} total events
                            </Badge>
                            <Badge className="border-border/70 bg-background/80 text-foreground">
                                {filters.search || filters.category || filters.type || filters.filter ? 'Filtered feed' : 'All events'}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-primary/10 shadow-sm">
                    <CardContent className="space-y-5 p-5">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Filters
                            </p>
                            <h3 className="text-lg font-semibold tracking-[-0.02em] text-foreground">
                                Narrow the event feed
                            </h3>
                        </div>
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
                            <Button
                                type="submit"
                                className="bg-[linear-gradient(135deg,hsl(var(--primary)),hsl(var(--primary))/0.82)] shadow-sm"
                            >
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

                {events.data.length === 0 ? (
                    <Card className="border-primary/10 shadow-sm">
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
                                    <Card
                                        key={event.id}
                                        className="flex flex-col overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_98%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))] shadow-[0_18px_36px_-30px_rgb(0_0_0_/_0.42)] transition-[transform,border-color,box-shadow] hover:-translate-y-0.5 hover:border-primary/15 hover:shadow-[0_24px_42px_-30px_rgb(96_44_193_/_0.2)]"
                                    >
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
                                                <Badge variant="outline" className="border-border/70 bg-background/88 capitalize text-foreground">
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
                                                        <span>PHP {parseFloat(event.price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                                    </div>
                                                )}
                                                {event.is_free && (
                                                    <div className="flex items-center gap-2">
                                                        <Badge className="border-transparent bg-success-soft text-success">
                                                            Free Event
                                                        </Badge>
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
                                                <Link href={preserveOrgParam(`/events/${event.id}`, currentOrgSlug)}>
                                                    View Details
                                                </Link>
                                            </Button>
                                        </CardFooter>
                                    </Card>
                                );
                            })}
                        </div>

                        {events.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
                                {Array.from({ length: events.last_page }, (_, i) => i + 1).map((page) => (
                                    <Button
                                        key={page}
                                        variant={page === events.current_page ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                preserveOrgParam('/events', currentOrgSlug),
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
