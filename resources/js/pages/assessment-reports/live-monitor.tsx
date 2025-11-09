import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import AssessmentReportsLayout from '@/layouts/assessment-reports/assessment-reports-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    Clock,
    Globe,
    RefreshCw,
    Wifi,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Assessment Reports',
        href: '/assessment-reports/live-monitor',
    },
];

interface ActiveSession {
    id: number;
    resident_name: string;
    resident_email: string;
    exam_title: string;
    exam_category: string | null;
    organization_name: string;
    started_at: string;
    time_elapsed: string;
    last_activity: string;
    is_idle: boolean;
    ip_address: string | null;
    browser: string;
    device: string;
    connection: string | null;
    speed: string;
    ip_changes: number;
    browser_changes: number;
    idle_time: string;
    idle_periods: number;
    is_suspicious: boolean;
}

interface Organization {
    id: number;
    name: string;
}

interface Exam {
    id: number;
    title: string;
}

interface PageProps {
    activeSessions: ActiveSession[];
    filters: {
        exam?: number;
        organization?: number;
        activity_status?: string;
    };
    organizations: Organization[];
    exams: Exam[];
    isSystemAdmin: boolean;
    lastUpdate: string;
    [key: string]: unknown;
}

export default function LiveExamMonitor() {
    const {
        activeSessions,
        filters,
        organizations,
        exams,
        isSystemAdmin,
        lastUpdate,
    } = usePage<PageProps>().props;

    const [examFilter, setExamFilter] = useState(
        filters.exam?.toString() || '',
    );
    const [organizationFilter, setOrganizationFilter] = useState(
        filters.organization?.toString() || '',
    );
    const [activityStatusFilter, setActivityStatusFilter] = useState(
        filters.activity_status || '',
    );
    const [autoRefresh, setAutoRefresh] = useState(true);

    // Auto-refresh every 10 seconds
    useEffect(() => {
        if (!autoRefresh) return;

        const interval = setInterval(() => {
            router.reload({ only: ['activeSessions', 'lastUpdate'] });
        }, 10000); // 10 seconds

        return () => clearInterval(interval);
    }, [autoRefresh]);

    const handleFilter = () => {
        router.get(
            '/assessment-reports/live-monitor',
            {
                exam: examFilter || undefined,
                organization: organizationFilter || undefined,
                activity_status: activityStatusFilter || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setExamFilter('');
        setOrganizationFilter('');
        setActivityStatusFilter('');
        router.get('/assessment-reports/live-monitor', {}, { preserveState: true });
    };

    const hasActiveFilters =
        filters.exam || filters.organization || filters.activity_status;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Live Exam Monitor" />

            <AssessmentReportsLayout>
                <div className="space-y-6">
                    <div className="flex items-start justify-between gap-4">
                        <HeadingSmall
                            title="Live Exam Monitor"
                            description="Real-time monitoring of active exam sessions"
                        />
                        <div className="flex items-center gap-2">
                            <Badge variant="secondary" className="gap-1">
                                <Clock className="h-3 w-3" />
                                {lastUpdate}
                            </Badge>
                            <Button
                                variant={autoRefresh ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setAutoRefresh(!autoRefresh)}
                            >
                                <RefreshCw
                                    className={`mr-2 h-4 w-4 ${autoRefresh ? 'animate-spin' : ''}`}
                                />
                                {autoRefresh ? 'Auto' : 'Manual'}
                            </Button>
                        </div>
                    </div>

                    {/* Filters */}
                    <Card>
                        <CardContent className="p-4">
                            <div className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Exam</Label>
                                        <Select
                                            value={examFilter}
                                            onValueChange={setExamFilter}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="All Exams" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {exams.map((exam) => (
                                                    <SelectItem
                                                        key={exam.id}
                                                        value={exam.id.toString()}
                                                    >
                                                        {exam.title}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {isSystemAdmin && (
                                        <div className="space-y-2">
                                            <Label>Institution</Label>
                                            <Select
                                                value={organizationFilter}
                                                onValueChange={
                                                    setOrganizationFilter
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="All Institutions" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {organizations.map(
                                                        (org) => (
                                                            <SelectItem
                                                                key={org.id}
                                                                value={org.id.toString()}
                                                            >
                                                                {org.name}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    )}

                                    <div className="space-y-2">
                                        <Label>Activity Status</Label>
                                        <Select
                                            value={activityStatusFilter}
                                            onValueChange={
                                                setActivityStatusFilter
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="All Status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">
                                                    Active
                                                </SelectItem>
                                                <SelectItem value="idle">
                                                    Idle
                                                </SelectItem>
                                                <SelectItem value="suspicious">
                                                    🚨 Suspicious
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div className="flex gap-2">
                                    <Button onClick={handleFilter}>
                                        Apply Filters
                                    </Button>
                                    {hasActiveFilters && (
                                        <Button
                                            variant="outline"
                                            onClick={clearFilters}
                                        >
                                            <X className="mr-2 h-4 w-4" />
                                            Clear
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Active Sessions */}
                    {activeSessions.length === 0 ? (
                        <Card>
                            <CardContent className="p-12 text-center">
                                <Activity className="mx-auto h-12 w-12 text-muted-foreground" />
                                <p className="mt-4 text-sm text-muted-foreground">
                                    {hasActiveFilters
                                        ? 'No active sessions matching your filters'
                                        : 'No residents are currently taking exams'}
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card>
                            <CardContent className="p-0">
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>
                                                    Resident
                                                </TableHead>
                                                {isSystemAdmin && (
                                                    <TableHead>
                                                        Institution
                                                    </TableHead>
                                                )}
                                                <TableHead>Exam</TableHead>
                                                <TableHead>
                                                    Time Elapsed
                                                </TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>
                                                    Connection
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    Changes
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    Idle
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {activeSessions.map((session) => (
                                                <TableRow
                                                    key={session.id}
                                                    className={
                                                        session.is_suspicious
                                                            ? 'bg-red-50 dark:bg-red-950/20'
                                                            : ''
                                                    }
                                                >
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">
                                                                {
                                                                    session.resident_name
                                                                }
                                                            </div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {
                                                                    session.resident_email
                                                                }
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    {isSystemAdmin && (
                                                        <TableCell className="text-sm">
                                                            {
                                                                session.organization_name
                                                            }
                                                        </TableCell>
                                                    )}
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">
                                                                {
                                                                    session.exam_title
                                                                }
                                                            </div>
                                                            {session.exam_category && (
                                                                <div className="text-xs text-muted-foreground">
                                                                    {
                                                                        session.exam_category
                                                                    }
                                                                </div>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm">
                                                            <div className="font-medium">
                                                                {
                                                                    session.time_elapsed
                                                                }
                                                            </div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {
                                                                    session.last_activity
                                                                }
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex flex-col gap-1">
                                                            {session.is_idle ? (
                                                                <Badge variant="outline">
                                                                    💤 Idle
                                                                </Badge>
                                                            ) : (
                                                                <Badge
                                                                    variant="default"
                                                                    className="gap-1"
                                                                >
                                                                    <Activity className="h-3 w-3" />
                                                                    Active
                                                                </Badge>
                                                            )}
                                                            {session.is_suspicious && (
                                                                <Badge variant="destructive">
                                                                    🚨 Flagged
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-sm">
                                                        <div className="space-y-1.5">
                                                            {session.ip_address && (
                                                                <div className="flex items-center gap-1.5">
                                                                    <Globe className="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                                                                    <span className="font-mono text-xs font-semibold text-blue-700 dark:text-blue-300">
                                                                        {
                                                                            session.ip_address
                                                                        }
                                                                    </span>
                                                                </div>
                                                            )}
                                                            <div className="flex items-center gap-1">
                                                                <Wifi className="h-3 w-3" />
                                                                <span className="text-xs">
                                                                    {
                                                                        session.connection
                                                                    }{' '}
                                                                    |{' '}
                                                                    {
                                                                        session.speed
                                                                    }
                                                                </span>
                                                            </div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {session.browser}{' '}
                                                                ({session.device})
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {(session.ip_changes >
                                                            0 ||
                                                            session.browser_changes >
                                                                0) && (
                                                            <div className="flex flex-col items-center gap-1">
                                                                {session.ip_changes >
                                                                    0 && (
                                                                    <Badge
                                                                        variant="destructive"
                                                                        className="text-xs"
                                                                    >
                                                                        IP:{' '}
                                                                        {
                                                                            session.ip_changes
                                                                        }
                                                                    </Badge>
                                                                )}
                                                                {session.browser_changes >
                                                                    0 && (
                                                                    <Badge
                                                                        variant="destructive"
                                                                        className="text-xs"
                                                                    >
                                                                        Browser:{' '}
                                                                        {
                                                                            session.browser_changes
                                                                        }
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        )}
                                                        {session.ip_changes ===
                                                            0 &&
                                                            session.browser_changes ===
                                                                0 && (
                                                                <span className="text-xs text-muted-foreground">
                                                                    None
                                                                </span>
                                                            )}
                                                    </TableCell>
                                                    <TableCell className="text-center text-sm">
                                                        {session.idle_periods >
                                                        0 ? (
                                                            <div>
                                                                <div className="font-medium">
                                                                    {
                                                                        session.idle_time
                                                                    }
                                                                </div>
                                                                <div className="text-xs text-muted-foreground">
                                                                    {
                                                                        session.idle_periods
                                                                    }{' '}
                                                                    {session.idle_periods ===
                                                                    1
                                                                        ? 'period'
                                                                        : 'periods'}
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <span className="text-xs text-muted-foreground">
                                                                None
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Summary Stats */}
                    {activeSessions.length > 0 && (
                        <div className="grid gap-4 sm:grid-cols-4">
                            <Card>
                                <CardContent className="p-4 text-center">
                                    <div className="text-2xl font-bold">
                                        {activeSessions.length}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        Active Sessions
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="p-4 text-center">
                                    <div className="text-2xl font-bold text-green-600">
                                        {
                                            activeSessions.filter(
                                                (s) => !s.is_idle,
                                            ).length
                                        }
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        Active
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="p-4 text-center">
                                    <div className="text-2xl font-bold text-yellow-600">
                                        {
                                            activeSessions.filter((s) => s.is_idle)
                                                .length
                                        }
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        Idle
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="p-4 text-center">
                                    <div className="flex items-center justify-center gap-1 text-2xl font-bold text-red-600">
                                        <AlertTriangle className="h-6 w-6" />
                                        {
                                            activeSessions.filter(
                                                (s) => s.is_suspicious,
                                            ).length
                                        }
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        Suspicious
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}
                </div>
            </AssessmentReportsLayout>
        </AppLayout>
    );
}

