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
import AppLayout from '@/layouts/app-layout';
import { preserveOrgParam } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Clock3,
    Filter,
    LifeBuoy,
    Search,
} from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Support', href: '/support' },
    { title: 'Queue', href: '/support/manage' },
];

interface Option {
    value: string;
    label: string;
}

interface TicketItem {
    id: number;
    ticket_number: string;
    title: string;
    category: string;
    priority: string;
    status: string;
    module_name: string | null;
    organization_name: string | null;
    creator_name: string | null;
    creator_email: string | null;
    assignee_name: string | null;
    details_preview: string;
    created_at: string;
    updated_at_human: string;
}

interface PaginatedTickets {
    data: TicketItem[];
    current_page: number;
    last_page: number;
}

interface PageProps {
    tickets: PaginatedTickets;
    summary: {
        total: number;
        open: number;
        in_review: number;
        resolved: number;
    };
    categories: Option[];
    priorities: Option[];
    statuses: Option[];
    assignees: Option[];
    filters: {
        search?: string;
        status?: string;
        priority?: string;
        category?: string;
        assignee_user_id?: string;
    };
    isAllOrganizationsContext: boolean;
}

function statusBadge(status: string) {
    switch (status) {
        case 'resolved':
            return (
                <Badge className="border-transparent bg-emerald-100 text-emerald-700">
                    Resolved
                </Badge>
            );
        case 'in_review':
            return (
                <Badge className="border-transparent bg-amber-100 text-amber-700">
                    In Review
                </Badge>
            );
        default:
            return (
                <Badge className="border-transparent bg-primary/12 text-primary">
                    Open
                </Badge>
            );
    }
}

function priorityBadge(priority: string) {
    switch (priority) {
        case 'high':
            return (
                <Badge className="border-transparent bg-rose-100 text-rose-700">
                    High
                </Badge>
            );
        case 'medium':
            return (
                <Badge className="border-transparent bg-amber-100 text-amber-700">
                    Medium
                </Badge>
            );
        default:
            return <Badge variant="outline">Low</Badge>;
    }
}

export default function SupportManage({
    tickets,
    summary,
    categories,
    priorities,
    statuses,
    assignees,
    filters,
    isAllOrganizationsContext,
}: PageProps) {
    const page = usePage<SharedData>();
    const currentOrgSlug = page.props.auth.currentOrganization?.slug;
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [priority, setPriority] = useState(filters.priority || 'all');
    const [category, setCategory] = useState(filters.category || 'all');
    const [assigneeUserId, setAssigneeUserId] = useState(
        filters.assignee_user_id || 'all',
    );

    const applyFilters = () => {
        router.get(
            preserveOrgParam('/support/manage', currentOrgSlug),
            {
                search: search || undefined,
                status: status === 'all' ? undefined : status,
                priority: priority === 'all' ? undefined : priority,
                category: category === 'all' ? undefined : category,
                assignee_user_id:
                    assigneeUserId === 'all' ? undefined : assigneeUserId,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const resetFilters = () => {
        setSearch('');
        setStatus('all');
        setPriority('all');
        setCategory('all');
        setAssigneeUserId('all');

        router.get(preserveOrgParam('/support/manage', currentOrgSlug), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const hasActiveFilters =
        search.trim() !== '' ||
        status !== 'all' ||
        priority !== 'all' ||
        category !== 'all' ||
        assigneeUserId !== 'all';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Support Queue" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <HeadingSmall
                        title="Support Queue"
                        description="Review support requests for the active organization, assign ownership, and move issues toward resolution."
                    />
                    <Button asChild variant="outline">
                        <Link href={preserveOrgParam('/support', currentOrgSlug)}>
                            Back to My Tickets
                        </Link>
                    </Button>
                </div>

                {isAllOrganizationsContext && (
                    <Card>
                        <CardContent className="p-5 text-sm text-muted-foreground">
                            You are reviewing the support queue across all organizations you can manage.
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard title="Total" value={summary.total} description="Tickets in scope" icon={LifeBuoy} />
                    <StatCard title="Open" value={summary.open} description="Needs triage" icon={AlertTriangle} />
                    <StatCard title="In Review" value={summary.in_review} description="Active investigation" icon={Clock3} />
                    <StatCard title="Resolved" value={summary.resolved} description="Closed out cleanly" icon={CheckCircle2} />
                </div>

                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Filter className="h-4 w-4" />
                            Queue Filters
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-3 xl:grid-cols-[minmax(0,1.6fr)_180px_180px_220px_220px_auto_auto]">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    onKeyDown={(event) => {
                                        if (event.key === 'Enter') {
                                            applyFilters();
                                        }
                                    }}
                                    placeholder="Search ticket, title, or requester..."
                                    className="pl-9"
                                />
                            </div>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    {statuses.map((item) => (
                                        <SelectItem key={item.value} value={item.value}>
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={priority} onValueChange={setPriority}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Priority" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Priorities</SelectItem>
                                    {priorities.map((item) => (
                                        <SelectItem key={item.value} value={item.value}>
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={category} onValueChange={setCategory}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Categories</SelectItem>
                                    {categories.map((item) => (
                                        <SelectItem key={item.value} value={item.value}>
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={assigneeUserId}
                                onValueChange={setAssigneeUserId}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Assignee" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Assignees</SelectItem>
                                    <SelectItem value="unassigned">
                                        Unassigned
                                    </SelectItem>
                                    {assignees.map((item) => (
                                        <SelectItem key={item.value} value={item.value}>
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button onClick={applyFilters}>Apply</Button>
                            <Button variant="outline" onClick={resetFilters}>
                                Reset
                            </Button>
                        </div>

                        {hasActiveFilters && (
                            <div className="flex flex-wrap gap-2">
                                {search.trim() !== '' && (
                                    <Badge variant="outline">Search: {search}</Badge>
                                )}
                                {status !== 'all' && (
                                    <Badge variant="outline">Status: {status.replace('_', ' ')}</Badge>
                                )}
                                {priority !== 'all' && (
                                    <Badge variant="outline">Priority: {priority}</Badge>
                                )}
                                {category !== 'all' && (
                                    <Badge variant="outline">
                                        Category: {category.replace('_', ' ')}
                                    </Badge>
                                )}
                                {assigneeUserId !== 'all' && (
                                    <Badge variant="outline">
                                        Assignee:{' '}
                                        {assigneeUserId === 'unassigned'
                                            ? 'Unassigned'
                                            : assignees.find(
                                                  (item) =>
                                                      item.value === assigneeUserId,
                                              )?.label || assigneeUserId}
                                    </Badge>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-base">Queue</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {tickets.data.length === 0 ? (
                            <div className="rounded-[1.25rem] border border-dashed border-border/80 p-8 text-center">
                                <p className="font-medium text-foreground">No tickets match these filters</p>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    Try broadening the filters or switching organization context.
                                </p>
                            </div>
                        ) : (
                            tickets.data.map((ticket) => (
                                <div
                                    key={ticket.id}
                                    className="rounded-[1.25rem] border border-border/70 bg-background/85 p-5"
                                >
                                    <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge variant="outline">{ticket.ticket_number}</Badge>
                                                {statusBadge(ticket.status)}
                                                {priorityBadge(ticket.priority)}
                                                <Badge variant="outline">
                                                    {ticket.category.replace('_', ' ')}
                                                </Badge>
                                            </div>
                                            <h3 className="text-base font-semibold text-foreground">
                                                {ticket.title}
                                            </h3>
                                            <p className="text-sm leading-6 text-muted-foreground">
                                                {ticket.details_preview}
                                            </p>
                                            <div className="flex flex-wrap gap-3 text-xs text-muted-foreground">
                                                <span>
                                                    Requested by {ticket.creator_name || 'Unknown'}
                                                </span>
                                                {ticket.assignee_name && (
                                                    <span>
                                                        Assigned to {ticket.assignee_name}
                                                    </span>
                                                )}
                                                {ticket.module_name && (
                                                    <span>
                                                        Module: {ticket.module_name}
                                                    </span>
                                                )}
                                                {ticket.organization_name && (
                                                    <span>
                                                        Organization: {ticket.organization_name}
                                                    </span>
                                                )}
                                                <span>Updated {ticket.updated_at_human}</span>
                                            </div>
                                        </div>
                                        <Button asChild variant="outline">
                                            <Link
                                                href={preserveOrgParam(
                                                    `/support/${ticket.id}`,
                                                    currentOrgSlug,
                                                )}
                                            >
                                                Review Ticket
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            ))
                        )}

                        {tickets.last_page > 1 && (
                            <div className="flex flex-wrap items-center justify-center gap-2 pt-2">
                                {Array.from(
                                    { length: tickets.last_page },
                                    (_, index) => index + 1,
                                ).map((pageNumber) => (
                                    <Button
                                        key={pageNumber}
                                        size="sm"
                                        variant={
                                            pageNumber === tickets.current_page
                                                ? 'default'
                                                : 'outline'
                                        }
                                        onClick={() =>
                                            router.get(
                                                preserveOrgParam(
                                                    '/support/manage',
                                                    currentOrgSlug,
                                                ),
                                                {
                                                    search: search || undefined,
                                                    status:
                                                        status === 'all'
                                                            ? undefined
                                                            : status,
                                                    priority:
                                                        priority === 'all'
                                                            ? undefined
                                                            : priority,
                                                    category:
                                                        category === 'all'
                                                            ? undefined
                                                            : category,
                                                    assignee_user_id:
                                                        assigneeUserId === 'all'
                                                            ? undefined
                                                            : assigneeUserId,
                                                    page: pageNumber,
                                                },
                                                {
                                                    preserveState: true,
                                                    preserveScroll: true,
                                                },
                                            )
                                        }
                                    >
                                        {pageNumber}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
