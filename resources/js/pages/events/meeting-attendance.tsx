import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import {
    Clock,
    Globe,
    Monitor,
    Search,
    Smartphone,
    Tablet,
    UserCheck,
    Video,
    Wifi,
} from 'lucide-react';
import { useState } from 'react';

interface Event {
    id: number;
    title: string;
    event_type: 'in-person' | 'virtual' | 'hybrid';
}

interface AttendanceMetadata {
    ip_address?: string;
    user_agent?: string;
    browser_metadata?: {
        browser: string;
        browserVersion: string;
        os: string;
        osVersion: string;
        device: string;
        screenResolution: string;
        language: string;
        timezone: string;
    };
    connection_type?: string;
    connection_speed?: number;
    attendance_type?: 'virtual' | 'in-person';
    device?: string;
    browser?: string;
}

interface Attendance {
    id: number;
    joined_at: string;
    left_at: string | null;
    last_seen_at: string | null;
    duration_seconds: number | null;
    status: 'joined' | 'active' | 'left' | 'timeout';
    metadata: AttendanceMetadata | null;
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
    event_registration: {
        id: number;
        registration_status: string;
    } | null;
}

interface PaginatedAttendances {
    data: Attendance[];
    total: number;
    current_page: number;
    last_page: number;
}

interface Stats {
    total_attendees: number;
    active_now: number;
    total_duration_minutes: number;
    average_duration_minutes: number;
}

interface PageProps {
    event: Event;
    attendances: PaginatedAttendances;
    stats: Stats;
    filters: {
        status?: string;
        search?: string;
        attendance_type?: string;
    };
}

const statusColors = {
    joined: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    left: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    timeout: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

const deviceIcons = {
    Mobile: Smartphone,
    Tablet: Tablet,
    Desktop: Monitor,
};

function formatDuration(seconds: number | null): string {
    if (!seconds) return 'N/A';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m ${secs}s`;
    }
    if (minutes > 0) {
        return `${minutes}m ${secs}s`;
    }
    return `${secs}s`;
}

function formatDateTime(dateString: string | null): string {
    if (!dateString) return 'N/A';
    try {
        return format(parseISO(dateString), 'MMM dd, yyyy h:mm:ss a');
    } catch {
        return dateString;
    }
}

export default function MeetingAttendance({
    event,
    attendances,
    stats,
    filters,
}: PageProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedAttendance, setSelectedAttendance] =
        useState<Attendance | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Events', href: '/events' },
        { title: 'Manage', href: '/events/manage' },
        { title: event.title, href: `/events/${event.id}` },
        {
            title: 'Meeting Attendance',
            href: `/events/${event.id}/meeting-attendance`,
        },
    ];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            `/events/${event.id}/meeting-attendance`,
            { ...filters, search: searchQuery },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get(
            `/events/${event.id}/meeting-attendance`,
            { ...filters, [key]: value || undefined },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${event.title} - Meeting Attendance`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Meeting Attendance"
                        description={`Track who attended ${event.title} with detailed metadata`}
                    />
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href={`/events/${event.id}/attendees`}>
                                View Registrations
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/events/manage">Back to Manage</Link>
                        </Button>
                    </div>
                </div>

                {/* Statistics */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">
                                Total Attendees
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.total_attendees}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">
                                Active Now
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600 dark:text-green-400">
                                {stats.active_now}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">
                                Total Duration
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {Math.round(stats.total_duration_minutes)} min
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">
                                Avg Duration
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {Math.round(stats.average_duration_minutes)} min
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <form
                            onSubmit={handleSearch}
                            className="flex flex-col gap-4 md:flex-row"
                        >
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Search by name or email..."
                                    value={searchQuery}
                                    onChange={(e) =>
                                        setSearchQuery(e.target.value)
                                    }
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                value={filters.status}
                                onValueChange={(value) =>
                                    handleFilterChange('status', value)
                                }
                            >
                                <SelectTrigger className="w-full md:w-[180px]">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="joined">Joined</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="left">Left</SelectItem>
                                    <SelectItem value="timeout">Timeout</SelectItem>
                                </SelectContent>
                            </Select>
                            {event.event_type === 'hybrid' && (
                                <Select
                                    value={filters.attendance_type}
                                    onValueChange={(value) =>
                                        handleFilterChange(
                                            'attendance_type',
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-full md:w-[180px]">
                                        <SelectValue placeholder="All Types" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="virtual">
                                            Virtual
                                        </SelectItem>
                                        <SelectItem value="in-person">
                                            In-Person
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                            <Button type="submit">Search</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Attendances List */}
                {attendances.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <UserCheck className="mb-4 h-12 w-12 text-muted-foreground" />
                            <p className="text-lg font-medium">
                                No attendance records found
                            </p>
                            <p className="text-muted-foreground">
                                {filters.search || filters.status
                                    ? 'Try adjusting your filters'
                                    : 'No one has attended yet'}
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <Card>
                            <CardContent className="p-0">
                                <div className="divide-y">
                                    {attendances.data.map((attendance) => {
                                        const metadata = attendance.metadata;
                                        const DeviceIcon =
                                            metadata?.browser_metadata?.device
                                                ? deviceIcons[
                                                      metadata.browser_metadata
                                                          .device as keyof typeof deviceIcons
                                                  ] || Monitor
                                                : Monitor;

                                        return (
                                            <div
                                                key={attendance.id}
                                                className="p-4 hover:bg-muted/50"
                                            >
                                                <div className="flex items-start justify-between gap-4">
                                                    <div className="flex-1 space-y-3">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <h4 className="font-medium">
                                                                {
                                                                    attendance
                                                                        .user.name
                                                                }
                                                            </h4>
                                                            <Badge
                                                                className={
                                                                    statusColors[
                                                                        attendance.status as keyof typeof statusColors
                                                                    ]
                                                                }
                                                            >
                                                                {
                                                                    attendance.status
                                                                }
                                                            </Badge>
                                                            {metadata
                                                                ?.attendance_type && (
                                                                <Badge variant="outline">
                                                                    {metadata.attendance_type ===
                                                                    'virtual'
                                                                        ? 'Virtual'
                                                                        : 'In-Person'}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <p className="text-sm text-muted-foreground">
                                                            {
                                                                attendance.user
                                                                    .email
                                                            }
                                                        </p>

                                                        {/* Metadata Grid */}
                                                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                                            {/* IP Address */}
                                                            {metadata
                                                                ?.ip_address && (
                                                                <div className="flex items-start gap-2 text-sm">
                                                                    <Globe className="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground" />
                                                                    <div>
                                                                        <p className="font-medium">
                                                                            IP Address
                                                                        </p>
                                                                        <p className="text-muted-foreground">
                                                                            {
                                                                                metadata.ip_address
                                                                            }
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            )}

                                                            {/* Duration */}
                                                            <div className="flex items-start gap-2 text-sm">
                                                                <Clock className="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground" />
                                                                <div>
                                                                    <p className="font-medium">
                                                                        Duration
                                                                    </p>
                                                                    <p className="text-muted-foreground">
                                                                        {formatDuration(
                                                                            attendance.duration_seconds,
                                                                        )}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            {/* Browser/Device */}
                                                            {metadata
                                                                ?.browser_metadata && (
                                                                <div className="flex items-start gap-2 text-sm">
                                                                    <DeviceIcon className="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground" />
                                                                    <div>
                                                                        <p className="font-medium">
                                                                            Device
                                                                        </p>
                                                                        <p className="text-muted-foreground">
                                                                            {
                                                                                metadata
                                                                                    .browser_metadata
                                                                                    .browser
                                                                            }{' '}
                                                                            {
                                                                                metadata
                                                                                    .browser_metadata
                                                                                    .browserVersion
                                                                            }{' '}
                                                                            on{' '}
                                                                            {
                                                                                metadata
                                                                                    .browser_metadata
                                                                                    .device
                                                                            }
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            )}

                                                            {/* Connection */}
                                                            {metadata
                                                                ?.connection_type && (
                                                                <div className="flex items-start gap-2 text-sm">
                                                                    <Wifi className="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground" />
                                                                    <div>
                                                                        <p className="font-medium">
                                                                            Connection
                                                                        </p>
                                                                        <p className="text-muted-foreground">
                                                                            {
                                                                                metadata.connection_type
                                                                            }
                                                                            {metadata.connection_speed &&
                                                                                ` (${metadata.connection_speed.toFixed(
                                                                                    2,
                                                                                )} Mbps)`}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            )}

                                                            {/* Join Time */}
                                                            <div className="flex items-start gap-2 text-sm">
                                                                <Video className="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground" />
                                                                <div>
                                                                    <p className="font-medium">
                                                                        Joined
                                                                    </p>
                                                                    <p className="text-muted-foreground">
                                                                        {formatDateTime(
                                                                            attendance.joined_at,
                                                                        )}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            {/* Left Time */}
                                                            {attendance.left_at && (
                                                                <div className="flex items-start gap-2 text-sm">
                                                                    <Video className="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground" />
                                                                    <div>
                                                                        <p className="font-medium">
                                                                            Left
                                                                        </p>
                                                                        <p className="text-muted-foreground">
                                                                            {formatDateTime(
                                                                                attendance.left_at,
                                                                            )}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* View Details Button */}
                                                    <Dialog>
                                                        <DialogTrigger asChild>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    setSelectedAttendance(
                                                                        attendance,
                                                                    )
                                                                }
                                                            >
                                                                View Details
                                                            </Button>
                                                        </DialogTrigger>
                                                        <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                                                            <DialogHeader>
                                                                <DialogTitle>
                                                                    Attendance
                                                                    Details
                                                                </DialogTitle>
                                                                <DialogDescription>
                                                                    Complete
                                                                    metadata for{' '}
                                                                    {
                                                                        attendance
                                                                            .user
                                                                            .name
                                                                    }
                                                                </DialogDescription>
                                                            </DialogHeader>
                                                            <div className="space-y-4">
                                                                {/* User Info */}
                                                                <div>
                                                                    <h4 className="font-semibold mb-2">
                                                                        User
                                                                        Information
                                                                    </h4>
                                                                    <div className="space-y-1 text-sm">
                                                                        <p>
                                                                            <span className="font-medium">
                                                                                Name:
                                                                            </span>{' '}
                                                                            {
                                                                                attendance
                                                                                    .user
                                                                                    .name
                                                                            }
                                                                        </p>
                                                                        <p>
                                                                            <span className="font-medium">
                                                                                Email:
                                                                            </span>{' '}
                                                                            {
                                                                                attendance
                                                                                    .user
                                                                                    .email
                                                                            }
                                                                        </p>
                                                                        <p>
                                                                            <span className="font-medium">
                                                                                Organization:
                                                                            </span>{' '}
                                                                            {
                                                                                attendance
                                                                                    .organization
                                                                                    .name
                                                                            }
                                                                        </p>
                                                                    </div>
                                                                </div>

                                                                <Separator />

                                                                {/* Timing */}
                                                                <div>
                                                                    <h4 className="font-semibold mb-2">
                                                                        Timing
                                                                    </h4>
                                                                    <div className="space-y-1 text-sm">
                                                                        <p>
                                                                            <span className="font-medium">
                                                                                Joined:
                                                                            </span>{' '}
                                                                            {formatDateTime(
                                                                                attendance.joined_at,
                                                                            )}
                                                                        </p>
                                                                        <p>
                                                                            <span className="font-medium">
                                                                                Last Seen:
                                                                            </span>{' '}
                                                                            {formatDateTime(
                                                                                attendance.last_seen_at,
                                                                            )}
                                                                        </p>
                                                                        {attendance.left_at && (
                                                                            <p>
                                                                                <span className="font-medium">
                                                                                    Left:
                                                                                </span>{' '}
                                                                                {formatDateTime(
                                                                                    attendance.left_at,
                                                                                )}
                                                                            </p>
                                                                        )}
                                                                        <p>
                                                                            <span className="font-medium">
                                                                                Duration:
                                                                            </span>{' '}
                                                                            {formatDuration(
                                                                                attendance.duration_seconds,
                                                                            )}
                                                                        </p>
                                                                        <p>
                                                                            <span className="font-medium">
                                                                                Status:
                                                                            </span>{' '}
                                                                            <Badge
                                                                                className={
                                                                                    statusColors[
                                                                                        attendance.status as keyof typeof statusColors
                                                                                    ]
                                                                                }
                                                                            >
                                                                                {
                                                                                    attendance.status
                                                                                }
                                                                            </Badge>
                                                                        </p>
                                                                    </div>
                                                                </div>

                                                                <Separator />

                                                                {/* Metadata */}
                                                                {metadata && (
                                                                    <>
                                                                        <div>
                                                                            <h4 className="font-semibold mb-2">
                                                                                Network
                                                                                Information
                                                                            </h4>
                                                                            <div className="space-y-1 text-sm">
                                                                                {metadata.ip_address && (
                                                                                    <p>
                                                                                        <span className="font-medium">
                                                                                            IP Address:
                                                                                        </span>{' '}
                                                                                        {
                                                                                            metadata.ip_address
                                                                                        }
                                                                                    </p>
                                                                                )}
                                                                                {metadata.connection_type && (
                                                                                    <p>
                                                                                        <span className="font-medium">
                                                                                            Connection Type:
                                                                                        </span>{' '}
                                                                                        {
                                                                                            metadata.connection_type
                                                                                        }
                                                                                    </p>
                                                                                )}
                                                                                {metadata.connection_speed && (
                                                                                    <p>
                                                                                        <span className="font-medium">
                                                                                            Connection Speed:
                                                                                        </span>{' '}
                                                                                        {metadata.connection_speed.toFixed(
                                                                                            2,
                                                                                        )}{' '}
                                                                                        Mbps
                                                                                    </p>
                                                                                )}
                                                                                {metadata.attendance_type && (
                                                                                    <p>
                                                                                        <span className="font-medium">
                                                                                            Attendance Type:
                                                                                        </span>{' '}
                                                                                        {metadata.attendance_type ===
                                                                                        'virtual'
                                                                                            ? 'Virtual'
                                                                                            : 'In-Person'}
                                                                                    </p>
                                                                                )}
                                                                            </div>
                                                                        </div>

                                                                        {metadata.browser_metadata && (
                                                                            <>
                                                                                <Separator />
                                                                                <div>
                                                                                    <h4 className="font-semibold mb-2">
                                                                                        Browser & Device
                                                                                    </h4>
                                                                                    <div className="space-y-1 text-sm">
                                                                                        <p>
                                                                                            <span className="font-medium">
                                                                                                Browser:
                                                                                            </span>{' '}
                                                                                            {
                                                                                                metadata
                                                                                                    .browser_metadata
                                                                                                    .browser
                                                                                            }{' '}
                                                                                            {
                                                                                                metadata
                                                                                                    .browser_metadata
                                                                                                    .browserVersion
                                                                                            }
                                                                                        </p>
                                                                                        <p>
                                                                                            <span className="font-medium">
                                                                                                Operating System:
                                                                                            </span>{' '}
                                                                                            {
                                                                                                metadata
                                                                                                    .browser_metadata
                                                                                                    .os
                                                                                            }{' '}
                                                                                            {
                                                                                                metadata
                                                                                                    .browser_metadata
                                                                                                    .osVersion
                                                                                            }
                                                                                        </p>
                                                                                        <p>
                                                                                            <span className="font-medium">
                                                                                                Device:
                                                                                            </span>{' '}
                                                                                            {
                                                                                                metadata
                                                                                                    .browser_metadata
                                                                                                    .device
                                                                                            }
                                                                                        </p>
                                                                                        <p>
                                                                                            <span className="font-medium">
                                                                                                Screen Resolution:
                                                                                            </span>{' '}
                                                                                            {
                                                                                                metadata
                                                                                                    .browser_metadata
                                                                                                    .screenResolution
                                                                                            }
                                                                                        </p>
                                                                                        <p>
                                                                                            <span className="font-medium">
                                                                                                Language:
                                                                                            </span>{' '}
                                                                                            {
                                                                                                metadata
                                                                                                    .browser_metadata
                                                                                                    .language
                                                                                            }
                                                                                        </p>
                                                                                        <p>
                                                                                            <span className="font-medium">
                                                                                                Timezone:
                                                                                            </span>{' '}
                                                                                            {
                                                                                                metadata
                                                                                                    .browser_metadata
                                                                                                    .timezone
                                                                                            }
                                                                                        </p>
                                                                                    </div>
                                                                                </div>
                                                                            </>
                                                                        )}

                                                                        {metadata.user_agent && (
                                                                            <>
                                                                                <Separator />
                                                                                <div>
                                                                                    <h4 className="font-semibold mb-2">
                                                                                        User Agent
                                                                                    </h4>
                                                                                    <p className="text-xs text-muted-foreground break-all">
                                                                                        {
                                                                                            metadata.user_agent
                                                                                        }
                                                                                    </p>
                                                                                </div>
                                                                            </>
                                                                        )}
                                                                    </>
                                                                )}
                                                            </div>
                                                        </DialogContent>
                                                    </Dialog>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Pagination */}
                        {attendances.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
                                {Array.from(
                                    { length: attendances.last_page },
                                    (_, i) => i + 1,
                                ).map((page) => (
                                    <Button
                                        key={page}
                                        variant={
                                            page === attendances.current_page
                                                ? 'default'
                                                : 'outline'
                                        }
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                `/events/${event.id}/meeting-attendance`,
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
            </div>
        </AppLayout>
    );
}

