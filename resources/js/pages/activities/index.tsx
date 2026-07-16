import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
import { BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Activity, CalendarRange, Filter, Search, ShieldCheck, Users } from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Activity',
        href: '/activities',
    },
];

interface ActivityItem {
    id: number;
    description: string;
    module: string | null;
    module_label: string;
    action: string;
    subject_label: string;
    subject_type: string | null;
    organization_name: string | null;
    actor: {
        name: string;
        email: string;
    } | null;
    changes: Array<{
        field: string;
        old: string;
        new: string;
    }>;
    created_at: string;
    created_at_human: string;
}

interface PaginatedActivities {
    data: ActivityItem[];
    total: number;
    current_page: number;
    last_page: number;
}

interface ActivityModule {
    value: string;
    label: string;
}

interface PageProps {
    activities: PaginatedActivities;
    summary: {
        total: number;
        today: number;
        actors: number;
        modules: number;
    };
    filters: {
        search?: string;
        module?: string;
        date_from?: string;
        date_to?: string;
    };
    modules: ActivityModule[];
}

export default function ActivitiesIndex({
    activities,
    summary,
    filters,
    modules,
}: PageProps) {
    const { auth } = usePage<SharedData>().props;
    const currentOrgSlug = auth.currentOrganization?.slug;
    const [search, setSearch] = useState(filters.search || '');
    const [module, setModule] = useState(filters.module || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const hasFilters = useMemo(
        () => Boolean(search || module || dateFrom || dateTo),
        [dateFrom, dateTo, module, search],
    );

    const applyFilters = () => {
        router.get(
            '/activities',
            {
                search: search || undefined,
                module: module || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                org: currentOrgSlug || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setSearch('');
        setModule('');
        setDateFrom('');
        setDateTo('');
        router.get('/activities', { org: currentOrgSlug || undefined }, { preserveState: true, preserveScroll: true });
    };

    const actionBadgeVariant = (action: string) => {
        switch (action) {
            case 'created':
                return 'default';
            case 'updated':
                return 'secondary';
            case 'deleted':
                return 'destructive';
            default:
                return 'outline';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Activity" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Activity"
                        description="Track system changes, updates, and audit history across the platform."
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Total Activity"
                        value={summary.total}
                        description="Matching current filters"
                        icon={Activity}
                    />
                    <StatCard
                        title="Today"
                        value={summary.today}
                        description="Logged today"
                        icon={CalendarRange}
                    />
                    <StatCard
                        title="Active Users"
                        value={summary.actors}
                        description="Distinct actors"
                        icon={Users}
                    />
                    <StatCard
                        title="Modules"
                        value={summary.modules}
                        description="Touched in this feed"
                        icon={ShieldCheck}
                    />
                </div>

                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Filter className="h-4 w-4" />
                            Filters
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-3 lg:grid-cols-[minmax(0,1.6fr)_220px_180px_180px_auto]">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    onKeyDown={(event) => {
                                        if (event.key === 'Enter') {
                                            applyFilters();
                                        }
                                    }}
                                    placeholder="Search description or actor..."
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                value={module || undefined}
                                onValueChange={setModule}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Modules" />
                                </SelectTrigger>
                                <SelectContent>
                                    {modules.map((item) => (
                                        <SelectItem
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Input
                                type="date"
                                value={dateFrom}
                                onChange={(event) =>
                                    setDateFrom(event.target.value)
                                }
                            />
                            <Input
                                type="date"
                                value={dateTo}
                                onChange={(event) =>
                                    setDateTo(event.target.value)
                                }
                            />
                            <div className="flex gap-2">
                                <Button onClick={applyFilters}>Apply</Button>
                                {hasFilters && (
                                    <Button
                                        variant="outline"
                                        onClick={clearFilters}
                                    >
                                        Clear
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-base">Activity Feed</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {activities.data.length === 0 ? (
                            <div className="py-16 text-center">
                                <Activity className="mx-auto h-12 w-12 text-muted-foreground" />
                                <p className="mt-4 text-sm font-medium">
                                    No activity found
                                </p>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    Try adjusting the filters or broaden the date
                                    range.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>When</TableHead>
                                                <TableHead>Module</TableHead>
                                                <TableHead>Action</TableHead>
                                                <TableHead>Record</TableHead>
                                                <TableHead>Organization</TableHead>
                                                <TableHead>Actor</TableHead>
                                                <TableHead>Details</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {activities.data.map((activity) => (
                                                <TableRow key={activity.id}>
                                                    <TableCell className="align-top">
                                                        <div className="space-y-1">
                                                            <div className="text-sm font-medium">
                                                                {new Date(
                                                                    activity.created_at,
                                                                ).toLocaleString()}
                                                            </div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {
                                                                    activity.created_at_human
                                                                }
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="align-top">
                                                        <Badge variant="outline">
                                                            {
                                                                activity.module_label
                                                            }
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="align-top">
                                                        <Badge
                                                            variant={actionBadgeVariant(
                                                                activity.action,
                                                            )}
                                                            className="capitalize"
                                                        >
                                                            {activity.action}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="align-top">
                                                        <div className="space-y-1">
                                                            <div className="text-sm font-medium">
                                                                {
                                                                    activity.subject_label
                                                                }
                                                            </div>
                                                            {activity.subject_type && (
                                                                <div className="text-xs text-muted-foreground">
                                                                    {
                                                                        activity.subject_type
                                                                    }
                                                                </div>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="align-top text-sm text-muted-foreground">
                                                        {activity.organization_name ||
                                                            'Not specified'}
                                                    </TableCell>
                                                    <TableCell className="align-top">
                                                        {activity.actor ? (
                                                            <div className="space-y-1">
                                                                <div className="text-sm font-medium">
                                                                    {
                                                                        activity
                                                                            .actor
                                                                            .name
                                                                    }
                                                                </div>
                                                                <div className="text-xs text-muted-foreground">
                                                                    {
                                                                        activity
                                                                            .actor
                                                                            .email
                                                                    }
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-muted-foreground">
                                                                System
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="align-top">
                                                        <div className="space-y-2">
                                                            <p className="text-sm text-foreground">
                                                                {
                                                                    activity.description
                                                                }
                                                            </p>
                                                            {activity.changes
                                                                .length > 0 && (
                                                                <div className="space-y-1">
                                                                    {activity.changes
                                                                        .slice(
                                                                            0,
                                                                            3,
                                                                        )
                                                                        .map(
                                                                            (
                                                                                change,
                                                                            ) => (
                                                                                <div
                                                                                    key={`${activity.id}-${change.field}`}
                                                                                    className="text-xs text-muted-foreground"
                                                                                >
                                                                                    <span className="font-medium text-foreground">
                                                                                        {
                                                                                            change.field
                                                                                        }
                                                                                    </span>
                                                                                    :{' '}
                                                                                    {
                                                                                        change.old
                                                                                    }{' '}
                                                                                    to{' '}
                                                                                    {
                                                                                        change.new
                                                                                    }
                                                                                </div>
                                                                            ),
                                                                        )}
                                                                    {activity
                                                                        .changes
                                                                        .length >
                                                                        3 && (
                                                                        <div className="text-xs text-muted-foreground">
                                                                            +
                                                                            {activity
                                                                                .changes
                                                                                .length -
                                                                                3}{' '}
                                                                            more
                                                                            changes
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>

                                {activities.last_page > 1 && (
                                    <div className="flex flex-wrap items-center justify-center gap-2">
                                        {Array.from(
                                            {
                                                length: activities.last_page,
                                            },
                                            (_, index) => index + 1,
                                        ).map((page) => (
                                            <Button
                                                key={page}
                                                size="sm"
                                                variant={
                                                    page ===
                                                    activities.current_page
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                onClick={() =>
                                                    router.get(
                                                        '/activities',
                                                        {
                                                            ...filters,
                                                            page,
                                                            org: currentOrgSlug || undefined,
                                                        },
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
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
