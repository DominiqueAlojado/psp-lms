import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import {
    CreateEventSheet,
    EditEventSheet,
    EventLogsSheet,
} from '@/components/events';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import {
    Calendar,
    Edit,
    Eye,
    FileText,
    Plus,
    Search,
    Trash2,
    Users,
} from 'lucide-react';
import { StatCard } from '@/components/stat-card';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Events', href: '/events' },
    { title: 'Manage Events', href: '/events/manage' },
];

interface Event {
    id: number;
    title: string;
    scope: 'organization' | 'system';
    description: string | null;
    event_category: string;
    event_type: string;
    start_date: string;
    is_published: boolean;
    registrations_count: number;
    creator: {
        id: number;
        name: string;
    };
}

interface PaginatedEvents {
    data: Event[];
    total: number;
    current_page: number;
    last_page: number;
}

interface PageProps {
    events: PaginatedEvents;
    filters: {
        status?: string;
        search?: string;
        scope?: string;
    };
    canCreateSystem?: boolean;
}

export default function ManageEvents({
    events,
    filters,
    canCreateSystem = false,
}: PageProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [createSheetOpen, setCreateSheetOpen] = useState(false);
    const [editSheetOpen, setEditSheetOpen] = useState(false);
    const [selectedEvent, setSelectedEvent] = useState<Event | null>(null);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [eventToDelete, setEventToDelete] = useState<Event | null>(null);
    const [viewingLogsEvent, setViewingLogsEvent] = useState<Event | null>(
        null,
    );
    const [showLogsSheet, setShowLogsSheet] = useState(false);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/events/manage',
            { ...filters, search: searchQuery },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleStatusChange = (value: string) => {
        router.get(
            '/events/manage',
            { ...filters, status: value },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleScopeChange = (value: string) => {
        router.get(
            '/events/manage',
            { ...filters, scope: value },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleDeleteClick = (event: Event) => {
        setEventToDelete(event);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (eventToDelete) {
            router.delete(`/events/${eventToDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setEventToDelete(null);
                },
            });
        }
    };

    const handleDeleteCancel = () => {
        setDeleteDialogOpen(false);
        setEventToDelete(null);
    };

    const handleEdit = (event: Event) => {
        setSelectedEvent(event);
        setEditSheetOpen(true);
    };

    const handleViewLogs = (event: Event) => {
        setViewingLogsEvent(event);
        setShowLogsSheet(true);
    };
    const publishedCount = events.data.filter((event) => event.is_published).length;
    const totalRegistrations = events.data.reduce(
        (sum, event) => sum + event.registrations_count,
        0,
    );
    const scopeCount = new Set(events.data.map((event) => event.scope)).size;

    const formatDate = (dateString: string) => {
        try {
            return format(parseISO(dateString), 'MMM dd, yyyy');
        } catch {
            return dateString;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Events" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <HeadingSmall
                        title="Manage Events"
                        description="Create and manage your organization's events"
                    />
                    <Button onClick={() => setCreateSheetOpen(true)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Create Event
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Events"
                        value={events.total}
                        description="Items in the current admin view"
                        icon={Calendar}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Published"
                        value={publishedCount}
                        description="Visible events on this page"
                        icon={Eye}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Registrations"
                        value={totalRegistrations}
                        description="Combined registrations for visible events"
                        icon={Users}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Scopes"
                        value={scopeCount}
                        description="Visibility scopes currently represented"
                        icon={FileText}
                        iconColor="text-primary"
                    />
                </div>

                <Card className="overflow-hidden border-primary/10 bg-[linear-gradient(135deg,rgba(248,244,255,0.98),rgba(255,255,255,0.94))]">
                    <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Admin workspace
                            </p>
                            <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                Coordinate publishing, edits, and registrations
                            </h3>
                            <p className="text-sm leading-6 text-muted-foreground">
                                Review event status, registration traction, and management actions from one streamlined surface.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">
                                {events.total} total events
                            </Badge>
                            <Badge variant="outline">
                                {filters.search || filters.status || filters.scope ? 'Filtered results' : 'Full list'}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                {/* Filters */}
                <Card className="border-primary/10 shadow-sm">
                    <CardContent className="space-y-5 p-5">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Filters
                            </p>
                            <h3 className="text-lg font-semibold tracking-[-0.02em] text-foreground">
                                Narrow the event inventory
                            </h3>
                        </div>
                        <form
                            onSubmit={handleSearch}
                            className="flex flex-col gap-4 md:flex-row"
                        >
                            <div className="relative flex-1">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Search events..."
                                    value={searchQuery}
                                    onChange={(e) =>
                                        setSearchQuery(e.target.value)
                                    }
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                value={filters.status}
                                onValueChange={handleStatusChange}
                            >
                                <SelectTrigger className="w-full md:w-[180px]">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="published">
                                        Published
                                    </SelectItem>
                                    <SelectItem value="draft">Draft</SelectItem>
                                </SelectContent>
                            </Select>
                            {canCreateSystem && (
                                <Select
                                    value={filters.scope}
                                    onValueChange={handleScopeChange}
                                >
                                    <SelectTrigger className="w-full md:w-[220px]">
                                        <SelectValue placeholder="All Scopes" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="organization">
                                            Organization Only
                                        </SelectItem>
                                        <SelectItem value="system">
                                            All Organizations
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                            <Button
                                type="submit"
                                className="bg-[linear-gradient(135deg,hsl(var(--primary)),hsl(var(--primary))/0.82)] shadow-sm"
                            >
                                Search
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Events List */}
                {events.data.length === 0 ? (
                    <Card className="border-primary/10 shadow-sm">
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Calendar className="mb-4 h-12 w-12 text-muted-foreground" />
                            <p className="text-lg font-medium">
                                No events found
                            </p>
                            <p className="mb-4 text-muted-foreground">
                                Create your first event to get started
                            </p>
                            <Button onClick={() => setCreateSheetOpen(true)}>
                                <Plus className="mr-2 h-4 w-4" />
                                Create Event
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <div className="space-y-4">
                            {events.data.map((event) => (
                                <Card
                                    key={event.id}
                                    className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]"
                                >
                                    <CardContent className="flex flex-col gap-4 pt-6 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="flex-1 space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge
                                                    variant="outline"
                                                    className="capitalize"
                                                >
                                                    {event.event_category.replace(
                                                        '-',
                                                        ' ',
                                                    )}
                                                </Badge>
                                                <Badge
                                                    variant={
                                                        event.is_published
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {event.is_published
                                                        ? 'Published'
                                                        : 'Draft'}
                                                </Badge>
                                                <Badge variant="outline">
                                                    {event.scope === 'system'
                                                        ? 'All Organizations'
                                                        : 'Organization Only'}
                                                </Badge>
                                            </div>
                                            <h3 className="font-semibold">
                                                {event.title}
                                            </h3>
                                            {event.description && (
                                                <p className="line-clamp-2 text-sm text-muted-foreground">
                                                    {event.description.replace(
                                                        /<[^>]*>/g,
                                                        '',
                                                    )}
                                                </p>
                                            )}
                                            <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                <div className="flex items-center gap-1">
                                                    <Calendar className="h-4 w-4" />
                                                    {formatDate(
                                                        event.start_date,
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    <Users className="h-4 w-4" />
                                                    {event.registrations_count}{' '}
                                                    registered
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                            >
                                                <Link
                                                    href={`/events/${event.id}`}
                                                >
                                                    <Eye className="mr-2 h-4 w-4" />
                                                    View
                                                </Link>
                                            </Button>
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                            >
                                                <Link
                                                    href={`/events/${event.id}/attendees`}
                                                >
                                                    <Users className="mr-2 h-4 w-4" />
                                                    Attendees
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleViewLogs(event)
                                                }
                                                title="View Activity Logs"
                                            >
                                                <FileText className="mr-2 h-4 w-4" />
                                                Logs
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleEdit(event)
                                                }
                                            >
                                                <Edit className="mr-2 h-4 w-4" />
                                                Edit
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleDeleteClick(event)
                                                }
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {/* Pagination */}
                        {events.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
                                {Array.from(
                                    { length: events.last_page },
                                    (_, i) => i + 1,
                                ).map((page) => (
                                    <Button
                                        key={page}
                                        variant={
                                            page === events.current_page
                                                ? 'default'
                                                : 'outline'
                                        }
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                '/events/manage',
                                                { ...filters, page },
                                                {
                                                    preserveState: true,
                                                    preserveScroll: true,
                                                },
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

                <CreateEventSheet
                    open={createSheetOpen}
                    onClose={() => setCreateSheetOpen(false)}
                />
                <EditEventSheet
                    open={editSheetOpen}
                    onClose={() => setEditSheetOpen(false)}
                    event={selectedEvent}
                />
                {viewingLogsEvent && (
                    <EventLogsSheet
                        open={showLogsSheet}
                        onOpenChange={(open) => {
                            setShowLogsSheet(open);
                            if (!open) {
                                setViewingLogsEvent(null);
                            }
                        }}
                        event={{
                            id: viewingLogsEvent.id,
                            title: viewingLogsEvent.title,
                        }}
                    />
                )}
                <DeleteConfirmationDialog
                    open={deleteDialogOpen}
                    title="Delete Event?"
                    itemName={eventToDelete?.title}
                    description="This will remove the event and all its registrations."
                    warningMessage="This action cannot be undone."
                    onConfirm={handleDeleteConfirm}
                    onCancel={handleDeleteCancel}
                />
            </div>
        </AppLayout>
    );
}
