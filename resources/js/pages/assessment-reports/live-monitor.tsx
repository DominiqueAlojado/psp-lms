import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
    Bell,
    BellOff,
    Clock,
    Eye,
    Globe,
    KeyRound,
    MonitorSmartphone,
    RefreshCw,
    ShieldAlert,
    Wifi,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Assessment Reports',
        href: '/assessment-reports/live-monitor',
    },
];

interface BrowserChangeDetail {
    from: string;
    to: string;
    time: string;
}

interface IdlePeriodDetail {
    started_at: string;
    ended_at: string;
    duration: string;
    duration_minutes: number;
}

interface IpChangeDetail {
    from: string;
    to: string;
    time: string;
}

interface ActiveAccountSession {
    id: string;
    short_id: string;
    ip_address: string | null;
    browser: string;
    last_activity: string;
}

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
    ip_change_details: IpChangeDetail[];
    browser_changes: number;
    browser_change_details: BrowserChangeDetail[];
    idle_time: string;
    idle_periods: number;
    idle_period_details: IdlePeriodDetail[];
    locked_session_id: string | null;
    locked_session_short_id: string | null;
    lock_session_is_active: boolean;
    active_account_sessions_count: number;
    active_account_sessions: ActiveAccountSession[];
    has_multiple_account_sessions: boolean;
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
    const [selectedSession, setSelectedSession] =
        useState<ActiveSession | null>(null);
    const [alarmEnabled, setAlarmEnabled] = useState(true);
    const previousSuspiciousCount = useRef(0);
    const suspiciousCount = useMemo(
        () => activeSessions.filter((session) => session.is_suspicious).length,
        [activeSessions],
    );
    const idleCount = useMemo(
        () => activeSessions.filter((session) => session.is_idle).length,
        [activeSessions],
    );
    const multiSessionCount = useMemo(
        () =>
            activeSessions.filter(
                (session) => session.has_multiple_account_sessions,
            ).length,
        [activeSessions],
    );

    // Function to play alarm sound
    const playAlarmSound = useCallback(() => {
        try {
            const AudioContextClass =
                window.AudioContext ||
                (
                    window as Window & {
                        webkitAudioContext?: typeof AudioContext;
                    }
                ).webkitAudioContext;

            if (!AudioContextClass) {
                console.warn('❌ Audio not supported in this browser');
                return;
            }

            const audioContext = new AudioContextClass();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            // Alert beep sound (frequency and pattern)
            oscillator.frequency.value = 800; // Hz
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);

            // Play 3 short beeps
            oscillator.start(audioContext.currentTime);
            gainNode.gain.setValueAtTime(0, audioContext.currentTime + 0.1);
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime + 0.2);
            gainNode.gain.setValueAtTime(0, audioContext.currentTime + 0.3);
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime + 0.4);
            gainNode.gain.setValueAtTime(0, audioContext.currentTime + 0.5);
            oscillator.stop(audioContext.currentTime + 0.5);

            console.log('Alarm sound played successfully');
        } catch (error) {
            console.error('❌ Failed to play alarm:', error);
        }
    }, []);

    // Play alarm when new suspicious activity is detected
    useEffect(() => {
        const suspiciousCount = activeSessions.filter(
            (s) => s.is_suspicious,
        ).length;

        console.log('Alarm check:', {
            alarmEnabled,
            suspiciousCount,
            previousCount: previousSuspiciousCount.current,
            willTrigger:
                alarmEnabled &&
                suspiciousCount > 0 &&
                suspiciousCount > previousSuspiciousCount.current,
        });

        // Check if suspicious count increased OR first detection
        if (
            alarmEnabled &&
            suspiciousCount > 0 &&
            suspiciousCount > previousSuspiciousCount.current
        ) {
            console.warn('ALARM: Suspicious activity detected!');
            console.log(
                `Flagged sessions: ${previousSuspiciousCount.current} -> ${suspiciousCount}`,
            );
            playAlarmSound();
        }

        previousSuspiciousCount.current = suspiciousCount;
    }, [activeSessions, alarmEnabled, playAlarmSound]);

    // Auto-refresh every 5 seconds
    useEffect(() => {
        if (!autoRefresh) return;

        const interval = setInterval(() => {
            router.reload({ only: ['activeSessions', 'lastUpdate'] });
        }, 5000); // 5 seconds

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
        router.get(
            '/assessment-reports/live-monitor',
            {},
            { preserveState: true },
        );
    };

    const hasActiveFilters =
        filters.exam || filters.organization || filters.activity_status;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Live Exam Monitor" />

            <AssessmentReportsLayout>
                <div className="space-y-6">
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <HeadingSmall
                            title="Live Exam Monitor"
                            description="Real-time visibility into active exam sessions, connection changes, and suspicious signals."
                        />
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary" className="gap-1">
                                <Clock className="h-3 w-3" />
                                {lastUpdate}
                            </Badge>
                            <Button
                                variant={alarmEnabled ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setAlarmEnabled(!alarmEnabled)}
                                title={
                                    alarmEnabled
                                        ? 'Alarm enabled - will sound when suspicious activity detected'
                                        : 'Alarm disabled'
                                }
                            >
                                {alarmEnabled ? (
                                    <Bell className="mr-2 h-4 w-4" />
                                ) : (
                                    <BellOff className="mr-2 h-4 w-4" />
                                )}
                                {alarmEnabled ? 'Alarm On' : 'Alarm Off'}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    console.log('Testing alarm sound...');
                                    playAlarmSound();
                                }}
                                title="Test the alarm sound"
                            >
                                <Bell className="mr-2 h-4 w-4" />
                                Test Alarm
                            </Button>
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

                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            title="Active Sessions"
                            value={activeSessions.length}
                            description="Residents currently taking exams"
                            icon={Activity}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Flagged"
                            value={suspiciousCount}
                            description="Sessions with suspicious behavior"
                            icon={AlertTriangle}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Idle"
                            value={idleCount}
                            description="Sessions currently inactive"
                            icon={Clock}
                            iconColor="text-primary"
                        />
                        <StatCard
                            title="Multi-Session"
                            value={multiSessionCount}
                            description="Accounts with multiple active sessions"
                            icon={MonitorSmartphone}
                            iconColor="text-primary"
                        />
                    </div>

                    <Card className="overflow-hidden border-primary/12 bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_88%,white)_0%,color-mix(in_oklab,var(--color-card)_96%,var(--color-accent))_100%)] dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_72%,black)_0%,color-mix(in_oklab,var(--color-card)_92%,var(--color-accent))_100%)]">
                        <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="space-y-1">
                                <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                    Monitor overview
                                </p>
                                <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                    Watch active exams before issues escalate
                                </h3>
                                <p className="text-sm leading-6 text-muted-foreground">
                                    Track session locks, device changes, idle behavior, and suspicious activity from one surface.
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Badge variant="secondary">
                                    {activeSessions.length} live
                                </Badge>
                                <Badge variant="outline">
                                    {suspiciousCount} flagged
                                </Badge>
                                <Badge variant="outline">
                                    {multiSessionCount} multi-session
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Filters */}
                    <Card className="border-primary/10 shadow-sm">
                        <CardContent className="space-y-5 p-5">
                            <div className="flex flex-col gap-1">
                                <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                    Filters
                                </p>
                                <h3 className="text-lg font-semibold tracking-[-0.02em] text-foreground">
                                    Narrow the live session feed
                                </h3>
                            </div>
                            <div className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label className="text-[0.7rem] font-semibold tracking-[0.14em] uppercase text-muted-foreground">
                                            Exam
                                        </Label>
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
                                            <Label className="text-[0.7rem] font-semibold tracking-[0.14em] uppercase text-muted-foreground">
                                                Institution
                                            </Label>
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
                                        <Label className="text-[0.7rem] font-semibold tracking-[0.14em] uppercase text-muted-foreground">
                                            Activity Status
                                        </Label>
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
                                                    Suspicious
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        onClick={handleFilter}
                                        className="bg-[linear-gradient(135deg,hsl(var(--primary)),hsl(var(--primary))/0.82)] shadow-sm"
                                    >
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
                        <Card className="border-primary/10 shadow-sm">
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
                        <Card className="border-primary/10 shadow-sm">
                            <CardContent className="p-0">
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Resident</TableHead>
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
                                                <TableHead>
                                                    Sessions
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
                                                            ? 'bg-destructive/5'
                                                            : 'hover:bg-muted/40'
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
                                                                    Idle
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
                                                                    Flagged
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-sm">
                                                        <div className="space-y-1.5">
                                                            {session.ip_address ? (
                                                                <div className="flex items-center gap-1.5">
                                                                    <Globe className="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                                                                    <span className="font-mono text-xs font-semibold text-blue-700 dark:text-blue-300">
                                                                        {
                                                                            session.ip_address
                                                                        }
                                                                    </span>
                                                                </div>
                                                            ) : (
                                                                <div className="flex items-center gap-1.5">
                                                                    <Globe className="h-3.5 w-3.5 text-muted-foreground" />
                                                                    <span className="text-xs text-muted-foreground italic">
                                                                        IP not
                                                                        captured
                                                                    </span>
                                                                </div>
                                                            )}
                                                            <div className="flex items-center gap-1">
                                                                <Wifi className="h-3 w-3 text-muted-foreground" />
                                                                <span className="text-xs text-muted-foreground">
                                                                    {session.connection ||
                                                                        'broadband'}{' '}
                                                                    {session.speed &&
                                                                        `| ${session.speed}`}
                                                                </span>
                                                            </div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {session.browser &&
                                                                session.browser !==
                                                                    'Unknown'
                                                                    ? `${session.browser}${session.device && session.device !== 'Unknown' ? ` (${session.device})` : ''}`
                                                                    : 'Detecting browser...'}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-sm">
                                                        <div className="space-y-1.5">
                                                            <div className="flex items-center gap-1.5">
                                                                <MonitorSmartphone className="h-3.5 w-3.5 text-muted-foreground" />
                                                                <span className="text-xs font-medium">
                                                                    {session.active_account_sessions_count}{' '}
                                                                    active
                                                                </span>
                                                            </div>
                                                            {session.locked_session_short_id ? (
                                                                <div className="flex items-center gap-1.5">
                                                                    <KeyRound className="h-3.5 w-3.5 text-muted-foreground" />
                                                                    <span className="font-mono text-xs text-muted-foreground">
                                                                        Lock:{' '}
                                                                        {session.locked_session_short_id}
                                                                    </span>
                                                                </div>
                                                            ) : (
                                                                <div className="text-xs text-muted-foreground">
                                                                    Lock not assigned
                                                                </div>
                                                            )}
                                                            <div className="flex flex-wrap gap-1.5">
                                                                {session.has_multiple_account_sessions && (
                                                                    <Badge
                                                                        variant="destructive"
                                                                        className="text-xs"
                                                                    >
                                                                        Multi-browser
                                                                    </Badge>
                                                                )}
                                                                {session.locked_session_id && (
                                                                    <Badge
                                                                        variant={
                                                                            session.lock_session_is_active
                                                                                ? 'secondary'
                                                                                : 'outline'
                                                                        }
                                                                        className="text-xs"
                                                                    >
                                                                        {session.lock_session_is_active
                                                                            ? 'Lock active'
                                                                            : 'Stale lock'}
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {(session.ip_changes > 0 ||
                                                            session.browser_changes > 0 ||
                                                            session.active_account_sessions_count >
                                                                0) && (
                                                            <div className="flex flex-col items-center gap-1.5">
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
                                                                {session.has_multiple_account_sessions && (
                                                                    <Badge
                                                                        variant="destructive"
                                                                        className="text-xs"
                                                                    >
                                                                        Sessions:{' '}
                                                                        {
                                                                            session.active_account_sessions_count
                                                                        }
                                                                    </Badge>
                                                                )}
                                                                {(session
                                                                    .browser_change_details
                                                                    .length >
                                                                    0 ||
                                                                    session.ip_changes >
                                                                        0 ||
                                                                    session.active_account_sessions_count >
                                                                        0) && (
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        className="h-7 text-xs"
                                                                        onClick={() =>
                                                                            setSelectedSession(
                                                                                session,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Eye className="mr-1 h-3 w-3" />
                                                                        See
                                                                        Details
                                                                    </Button>
                                                                )}
                                                            </div>
                                                        )}
                                                        {session.ip_changes ===
                                                            0 &&
                                                            session.active_account_sessions_count ===
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

                </div>
            </AssessmentReportsLayout>

            {/* Session Change Details Modal */}
            <Dialog
                open={selectedSession !== null}
                onOpenChange={(open) => !open && setSelectedSession(null)}
            >
                <DialogContent className="max-h-[80vh] max-w-2xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Session Monitoring Details</DialogTitle>
                        <DialogDescription>
                            Detailed exam lock, account session, browser, and
                            IP monitoring data for this attempt
                        </DialogDescription>
                    </DialogHeader>

                    {selectedSession && (
                        <div className="space-y-6">
                            {/* Student Info */}
                            <div className="rounded-lg border bg-muted/50 p-4">
                                <div className="space-y-2">
                                    <div>
                                        <span className="text-sm font-semibold">
                                            Resident:
                                        </span>{' '}
                                        <span className="text-sm">
                                            {selectedSession.resident_name}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-sm font-semibold">
                                            Email:
                                        </span>{' '}
                                        <span className="text-sm">
                                            {selectedSession.resident_email}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-sm font-semibold">
                                            Exam:
                                        </span>{' '}
                                        <span className="text-sm">
                                            {selectedSession.exam_title}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <h3 className="font-semibold">
                                    Account Session Visibility
                                </h3>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Card>
                                        <CardContent className="space-y-3 p-4">
                                            <div className="flex items-center gap-2">
                                                <MonitorSmartphone className="h-4 w-4 text-muted-foreground" />
                                                <span className="text-sm font-medium">
                                                    Active web sessions
                                                </span>
                                            </div>
                                            <div className="text-3xl font-bold">
                                                {
                                                    selectedSession.active_account_sessions_count
                                                }
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                Active Laravel sessions currently
                                                tied to this resident account
                                            </div>
                                            {selectedSession.has_multiple_account_sessions && (
                                                <Badge
                                                    variant="destructive"
                                                    className="w-fit"
                                                >
                                                    Multiple browsers/devices detected
                                                </Badge>
                                            )}
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardContent className="space-y-3 p-4">
                                            <div className="flex items-center gap-2">
                                                <KeyRound className="h-4 w-4 text-muted-foreground" />
                                                <span className="text-sm font-medium">
                                                    Exam lock owner
                                                </span>
                                            </div>
                                            <div className="font-mono text-2xl font-bold">
                                                {selectedSession.locked_session_short_id ??
                                                    'None'}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                Session currently holding the
                                                in-progress exam lock
                                            </div>
                                            {selectedSession.locked_session_id ? (
                                                <Badge
                                                    variant={
                                                        selectedSession.lock_session_is_active
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                    className="w-fit"
                                                >
                                                    {selectedSession.lock_session_is_active
                                                        ? 'Lock points to an active session'
                                                        : 'Lock points to a stale session'}
                                                </Badge>
                                            ) : (
                                                <Badge
                                                    variant="outline"
                                                    className="w-fit"
                                                >
                                                    No exam lock stored
                                                </Badge>
                                            )}
                                        </CardContent>
                                    </Card>
                                </div>

                                {selectedSession.active_account_sessions.length >
                                0 ? (
                                    <div className="rounded-lg border">
                                        <div className="divide-y">
                                            {selectedSession.active_account_sessions.map(
                                                (accountSession) => (
                                                    <div
                                                        key={accountSession.id}
                                                        className="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between"
                                                    >
                                                        <div className="space-y-1">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <Badge
                                                                    variant="outline"
                                                                    className="font-mono text-xs"
                                                                >
                                                                    {accountSession.short_id}
                                                                </Badge>
                                                                {selectedSession.locked_session_id ===
                                                                    accountSession.id && (
                                                                    <Badge className="text-xs">
                                                                        Lock owner
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                            <div className="text-sm">
                                                                {accountSession.browser}
                                                            </div>
                                                            <div className="font-mono text-xs text-muted-foreground">
                                                                {accountSession.ip_address ??
                                                                    'IP not captured'}
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                            <Clock className="h-3.5 w-3.5" />
                                                            <span>
                                                                Last active{' '}
                                                                {
                                                                    accountSession.last_activity
                                                                }
                                                            </span>
                                                        </div>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                        No active Laravel web sessions were found
                                        for this resident right now.
                                    </div>
                                )}
                            </div>

                            {/* Summary Cards */}
                            <div className="grid gap-4 sm:grid-cols-2">
                                {selectedSession.ip_changes > 0 &&
                                    (() => {
                                        // Calculate unique IPs
                                        const ips = new Set<string>();
                                        selectedSession.ip_change_details.forEach(
                                            (change) => {
                                                ips.add(change.from);
                                                ips.add(change.to);
                                            },
                                        );
                                        const uniqueIpCount = ips.size;

                                        return (
                                            <Card>
                                                <CardContent className="p-4 text-center">
                                                    <Badge
                                                        variant="destructive"
                                                        className="mb-2"
                                                    >
                                                        IP Changes
                                                    </Badge>
                                                    <div className="text-3xl font-bold text-red-600">
                                                        {
                                                            selectedSession.ip_changes
                                                        }
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {
                                                            selectedSession.ip_changes
                                                        }{' '}
                                                        {selectedSession.ip_changes ===
                                                        1
                                                            ? 'change'
                                                            : 'changes'}{' '}
                                                        between {uniqueIpCount}{' '}
                                                        {uniqueIpCount === 1
                                                            ? 'IP address'
                                                            : 'IP addresses'}
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        );
                                    })()}
                                {selectedSession.browser_changes > 0 &&
                                    (() => {
                                        // Calculate unique browsers
                                        const browsers = new Set<string>();
                                        selectedSession.browser_change_details.forEach(
                                            (change) => {
                                                browsers.add(change.from);
                                                browsers.add(change.to);
                                            },
                                        );
                                        const uniqueBrowserCount =
                                            browsers.size;

                                        return (
                                            <Card>
                                                <CardContent className="p-4 text-center">
                                                    <Badge
                                                        variant="destructive"
                                                        className="mb-2"
                                                    >
                                                        Browser Switches
                                                    </Badge>
                                                    <div className="text-3xl font-bold text-red-600">
                                                        {
                                                            selectedSession.browser_changes
                                                        }
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {
                                                            selectedSession.browser_changes
                                                        }{' '}
                                                        {selectedSession.browser_changes ===
                                                        1
                                                            ? 'switch'
                                                            : 'switches'}{' '}
                                                        between{' '}
                                                        {uniqueBrowserCount}{' '}
                                                        {uniqueBrowserCount ===
                                                        1
                                                            ? 'browser'
                                                            : 'browsers'}
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        );
                                    })()}
                            </div>

                            {/* IP Change Details */}
                            {selectedSession.ip_change_details &&
                                selectedSession.ip_change_details.length >
                                    0 && (
                                    <div className="space-y-3">
                                        <h3 className="font-semibold">
                                            IP Address Change History
                                        </h3>
                                        <div className="rounded-lg border">
                                            <div className="divide-y">
                                                {selectedSession.ip_change_details.map(
                                                    (change, idx) => (
                                                        <div
                                                            key={idx}
                                                            className="flex items-center justify-between p-3 hover:bg-muted/50"
                                                        >
                                                            <div className="flex items-center gap-2">
                                                                <Badge
                                                                    variant="outline"
                                                                    className="font-mono text-xs"
                                                                >
                                                                    #{idx + 1}
                                                                </Badge>
                                                                <div className="flex items-center gap-2">
                                                                    <span className="font-mono text-sm text-muted-foreground">
                                                                        {
                                                                            change.from
                                                                        }
                                                                    </span>
                                                                    <span className="text-muted-foreground">
                                                                        {'->'}
                                                                    </span>
                                                                    <span className="font-mono font-semibold text-red-600 dark:text-red-400">
                                                                        {
                                                                            change.to
                                                                        }
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <Badge
                                                                variant="secondary"
                                                                className="font-mono text-xs"
                                                            >
                                                                <Clock className="mr-1 h-3 w-3" />
                                                                {change.time}
                                                            </Badge>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                )}

                            {/* Browser Change Details */}
                            {selectedSession.browser_change_details.length >
                                0 && (
                                <div className="space-y-3">
                                    <h3 className="font-semibold">
                                        Browser Switch History
                                    </h3>
                                    <div className="rounded-lg border">
                                        <div className="divide-y">
                                            {selectedSession.browser_change_details.map(
                                                (change, idx) => (
                                                    <div
                                                        key={idx}
                                                        className="flex items-center justify-between p-3 hover:bg-muted/50"
                                                    >
                                                        <div className="flex items-center gap-2">
                                                            <Badge
                                                                variant="outline"
                                                                className="font-mono text-xs"
                                                            >
                                                                #{idx + 1}
                                                            </Badge>
                                                            <div className="flex items-center gap-2">
                                                                <span className="text-sm text-muted-foreground">
                                                                    {
                                                                        change.from
                                                                    }
                                                                </span>
                                                                <span className="text-muted-foreground">
                                                                    {'->'}
                                                                </span>
                                                                <span className="font-semibold text-red-600 dark:text-red-400">
                                                                    {change.to}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <Badge
                                                            variant="secondary"
                                                            className="font-mono text-xs"
                                                        >
                                                            <Clock className="mr-1 h-3 w-3" />
                                                            {change.time}
                                                        </Badge>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Idle Period Details */}
                            {selectedSession.idle_period_details &&
                                selectedSession.idle_period_details.length >
                                    0 && (
                                    <div className="space-y-3">
                                        <h3 className="font-semibold">
                                            Idle Period History
                                        </h3>
                                        <div className="rounded-lg border">
                                            <div className="divide-y">
                                                {selectedSession.idle_period_details.map(
                                                    (period, idx) => (
                                                        <div
                                                            key={idx}
                                                            className="flex items-center justify-between p-3 hover:bg-muted/50"
                                                        >
                                                            <div className="flex items-center gap-2">
                                                                <Badge
                                                                    variant="outline"
                                                                    className="font-mono text-xs"
                                                                >
                                                                    #{idx + 1}
                                                                </Badge>
                                                                <div className="flex flex-col gap-0.5">
                                                                    <div className="flex items-center gap-1.5 text-sm">
                                                                        <Clock className="h-3 w-3 text-muted-foreground" />
                                                                        <span className="text-muted-foreground">
                                                                            Started:
                                                                        </span>
                                                                        <span className="font-medium">
                                                                            {
                                                                                period.started_at
                                                                            }
                                                                        </span>
                                                                    </div>
                                                                    <div className="flex items-center gap-1.5 text-sm">
                                                                        <Clock className="h-3 w-3 text-muted-foreground" />
                                                                        <span className="text-muted-foreground">
                                                                            Ended:
                                                                        </span>
                                                                        <span className="font-medium">
                                                                            {
                                                                                period.ended_at
                                                                            }
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <Badge
                                                                variant="secondary"
                                                                className="font-mono text-xs"
                                                            >
                                                                {
                                                                    period.duration
                                                                }{' '}
                                                                (
                                                                {
                                                                    period.duration_minutes
                                                                }{' '}
                                                                min)
                                                            </Badge>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                )}

                            {/* Current Connection Info */}
                            <div className="space-y-3">
                                <h3 className="font-semibold">
                                    Current Connection
                                </h3>
                                <div className="space-y-2 rounded-lg border p-4">
                                    <div className="flex items-center gap-2">
                                        <Globe className="h-4 w-4 text-muted-foreground" />
                                        <span className="text-sm font-medium">
                                            IP Address:
                                        </span>
                                        <span className="font-mono text-sm text-blue-600 dark:text-blue-400">
                                            {selectedSession.ip_address ||
                                                'Not captured'}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Wifi className="h-4 w-4 text-muted-foreground" />
                                        <span className="text-sm font-medium">
                                            Connection:
                                        </span>
                                        <span className="text-sm">
                                            {selectedSession.connection ||
                                                'Unknown'}{' '}
                                            | {selectedSession.speed || 'N/A'}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Activity className="h-4 w-4 text-muted-foreground" />
                                        <span className="text-sm font-medium">
                                            Browser:
                                        </span>
                                        <span className="text-sm">
                                            {selectedSession.browser} (
                                            {selectedSession.device})
                                        </span>
                                    </div>
                                    {selectedSession.has_multiple_account_sessions && (
                                        <div className="flex items-center gap-2">
                                            <ShieldAlert className="h-4 w-4 text-amber-600" />
                                            <span className="text-sm font-medium">
                                                Admin signal:
                                            </span>
                                            <span className="text-sm text-amber-700 dark:text-amber-400">
                                                Multiple active account sessions
                                                were detected for this resident.
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
