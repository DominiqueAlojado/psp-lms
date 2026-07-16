import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { preserveOrgParam } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Clock3,
    LifeBuoy,
    MessageSquareText,
    Send,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Support',
        href: '/support',
    },
];

interface Option {
    value: string;
    label: string;
}

interface SupportTicketItem {
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
    data: SupportTicketItem[];
    total: number;
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
    organizations: Option[];
    filters: {
        status?: string;
        organization_id?: string;
    };
    canManage: boolean;
    isAllOrganizationsContext: boolean;
    canCreateTicket: boolean;
    showsManagedTickets: boolean;
}

function statusBadge(status: string) {
    switch (status) {
        case 'resolved':
            return (
                <Badge className="border-transparent bg-success-soft text-success">
                    Resolved
                </Badge>
            );
        case 'in_review':
            return (
                <Badge className="border-transparent bg-warning-soft text-warning">
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
                <Badge className="border-transparent bg-danger-soft text-danger">
                    High
                </Badge>
            );
        case 'medium':
            return (
                <Badge className="border-transparent bg-warning-soft text-warning">
                    Medium
                </Badge>
            );
        default:
            return <Badge variant="outline">Low</Badge>;
    }
}

export default function SupportIndex({
    tickets,
    summary,
    categories,
    priorities,
    statuses,
    organizations,
    filters,
    canManage,
    isAllOrganizationsContext,
    canCreateTicket,
    showsManagedTickets,
}: PageProps) {
    const page = usePage<SharedData>();
    const currentOrgSlug = page.props.auth.currentOrganization?.slug;
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [organizationFilter, setOrganizationFilter] = useState(
        filters.organization_id || 'all',
    );
    const form = useForm({
        title: '',
        category: categories[0]?.value ?? 'bug',
        priority: priorities[1]?.value ?? 'medium',
        module_name: '',
        page_url: '',
        details: '',
    });

    const applyFilter = (value: string, nextOrganization?: string) => {
        const resolvedOrganization = nextOrganization ?? organizationFilter;
        setStatusFilter(value);

        router.get(
            preserveOrgParam('/support', currentOrgSlug),
            {
                status: value === 'all' ? undefined : value,
                organization_id:
                    resolvedOrganization === 'all'
                        ? undefined
                        : resolvedOrganization,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const submit = () => {
        if (!canCreateTicket) {
            toast.error('Select a specific organization before creating a support ticket.');
            return;
        }

        form.post(preserveOrgParam('/support', currentOrgSlug), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Support ticket created successfully!');
                form.reset();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Customer Support" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <HeadingSmall
                        title="Customer Support"
                        description="Create real tickets, follow progress, and keep communication in one place."
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button
                            asChild
                            variant="outline"
                            className="border-primary/20 text-primary"
                        >
                            <Link
                                href={preserveOrgParam('/feedback', currentOrgSlug)}
                            >
                                <MessageSquareText className="mr-2 h-4 w-4" />
                                Leave Feedback
                            </Link>
                        </Button>
                        {canManage && (
                            <Button asChild variant="outline">
                                <Link
                                    href={preserveOrgParam(
                                        '/support/manage',
                                        currentOrgSlug,
                                    )}
                                >
                                    View Queue
                                </Link>
                            </Button>
                        )}
                        <Button
                            onClick={submit}
                            disabled={form.processing || !canCreateTicket}
                            className="border-transparent bg-[linear-gradient(135deg,#7c3aed,#c026d3)] text-white shadow-[0_18px_36px_-22px_rgb(124_58_237_/_0.58)] hover:brightness-[1.03]"
                        >
                            <Send className="mr-2 h-4 w-4" />
                            Submit Ticket
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="All Tickets"
                        value={summary.total}
                        description={
                            showsManagedTickets
                                ? 'Support requests in scope'
                                : 'Your submitted requests'
                        }
                        icon={LifeBuoy}
                    />
                    <StatCard
                        title="Open"
                        value={summary.open}
                        description="Waiting for triage"
                        icon={AlertTriangle}
                    />
                    <StatCard
                        title="In Review"
                        value={summary.in_review}
                        description="Actively being worked on"
                        icon={Clock3}
                    />
                    <StatCard
                        title="Resolved"
                        value={summary.resolved}
                        description="Completed support requests"
                        icon={CheckCircle2}
                    />
                </div>

                <Card className="overflow-hidden border-primary/12 bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_88%,white)_0%,color-mix(in_oklab,var(--color-card)_96%,var(--color-accent))_100%)] dark:bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-accent)_72%,black)_0%,color-mix(in_oklab,var(--color-card)_92%,var(--color-accent))_100%)]">
                    <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Support hub
                            </p>
                            <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                Real ticket submission and tracking
                            </h3>
                            <p className="text-sm leading-6 text-muted-foreground">
                                This module now stores tickets in the database,
                                keeps a thread per request, and gives staff a
                                real queue to manage.
                            </p>
                            {isAllOrganizationsContext && (
                                <p className="text-sm leading-6 text-muted-foreground">
                                    You are viewing support across all organizations.
                                    Choose a specific organization from the switcher to create a new ticket.
                                </p>
                            )}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">Database-backed</Badge>
                            <Badge variant="outline">Thread-ready support flow</Badge>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <div className="space-y-6">
                        <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                            <CardHeader className="flex flex-col gap-3 pb-3 lg:flex-row lg:items-center lg:justify-between">
                                <CardTitle>
                                    {showsManagedTickets ? 'Organization Tickets' : 'Your Tickets'}
                                </CardTitle>
                                <div className="flex w-full flex-col gap-3 sm:flex-row sm:justify-end">
                                    {isAllOrganizationsContext && canManage && (
                                        <div className="w-full sm:max-w-[240px]">
                                            <Select
                                                value={organizationFilter}
                                                onValueChange={(value) => {
                                                    setOrganizationFilter(value);
                                                    applyFilter(statusFilter, value);
                                                }}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="All Organizations" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">
                                                        All Organizations
                                                    </SelectItem>
                                                    {organizations.map((organization) => (
                                                        <SelectItem
                                                            key={organization.value}
                                                            value={organization.value}
                                                        >
                                                            {organization.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    )}
                                    <div className="w-full sm:max-w-[220px]">
                                        <Select
                                            value={statusFilter}
                                            onValueChange={(value) =>
                                                applyFilter(value)
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="All Statuses" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All Statuses
                                                </SelectItem>
                                                {statuses.map((status) => (
                                                    <SelectItem
                                                        key={status.value}
                                                        value={status.value}
                                                    >
                                                        {status.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {tickets.data.length === 0 ? (
                                    <div className="rounded-[1.25rem] border border-dashed border-border/80 p-8 text-center">
                                        <p className="font-medium text-foreground">
                                            {showsManagedTickets
                                                ? 'No support tickets found'
                                                : 'No support tickets yet'}
                                        </p>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {showsManagedTickets
                                                ? 'There are no tickets matching the current scope and filters.'
                                                : 'Submit your first ticket using the form on this page.'}
                                        </p>
                                    </div>
                                ) : (
                                    tickets.data.map((ticket) => (
                                        <div
                                            key={ticket.id}
                                            className="rounded-[1.35rem] border border-border/75 bg-background/88 p-5 shadow-[0_18px_34px_-30px_rgb(35_24_74_/_0.18)] dark:shadow-[0_18px_34px_-28px_rgb(0_0_0_/_0.44)]"
                                        >
                                            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                <div className="space-y-2">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Badge variant="outline">
                                                            {ticket.ticket_number}
                                                        </Badge>
                                                        {statusBadge(ticket.status)}
                                                        {priorityBadge(ticket.priority)}
                                                    </div>
                                                    <h3 className="text-base font-semibold text-foreground">
                                                        {ticket.title}
                                                    </h3>
                                                    <p className="text-sm leading-6 text-muted-foreground">
                                                        {ticket.details_preview}
                                                    </p>
                                                    <div className="flex flex-wrap gap-2 text-xs text-muted-foreground">
                                                        <span>
                                                            Category:{' '}
                                                            {ticket.category.replace(
                                                                '_',
                                                                ' ',
                                                            )}
                                                        </span>
                                                        {ticket.module_name && (
                                                            <span>
                                                                Module:{' '}
                                                                {ticket.module_name}
                                                            </span>
                                                        )}
                                                        {isAllOrganizationsContext &&
                                                            ticket.organization_name && (
                                                                <span>
                                                                    Organization:{' '}
                                                                    {ticket.organization_name}
                                                                </span>
                                                            )}
                                                        <span>
                                                            Updated{' '}
                                                            {ticket.updated_at_human}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="flex shrink-0 items-start gap-2">
                                                    {ticket.assignee_name && (
                                                        <Badge
                                                            variant="outline"
                                                            className="hidden lg:inline-flex"
                                                        >
                                                            {ticket.assignee_name}
                                                        </Badge>
                                                    )}
                                                    <Button asChild variant="outline">
                                                        <Link
                                                            href={preserveOrgParam(
                                                                `/support/${ticket.id}`,
                                                                currentOrgSlug,
                                                            )}
                                                        >
                                                            Open Ticket
                                                        </Link>
                                                    </Button>
                                                </div>
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
                                                    pageNumber ===
                                                    tickets.current_page
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                onClick={() =>
                                                    router.get(
                                                        preserveOrgParam(
                                                            '/support',
                                                            currentOrgSlug,
                                                        ),
                                                        {
                                                            status:
                                                                statusFilter ===
                                                                'all'
                                                                    ? undefined
                                                                    : statusFilter,
                                                            organization_id:
                                                                organizationFilter ===
                                                                'all'
                                                                    ? undefined
                                                                    : organizationFilter,
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

                    <div className="space-y-6">
                        <Card className="overflow-hidden border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                            <CardHeader className="pb-3">
                                <CardTitle>Create a Ticket</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="ticket-title"
                                        className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                                    >
                                        Ticket Title
                                    </Label>
                                    <Input
                                        id="ticket-title"
                                        value={form.data.title}
                                        onChange={(event) =>
                                            form.setData(
                                                'title',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Briefly describe the issue"
                                    />
                                    {form.errors.title && (
                                        <p className="text-sm text-destructive">
                                            {form.errors.title}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                            Category
                                        </Label>
                                        <Select
                                            value={form.data.category}
                                            onValueChange={(value) =>
                                                form.setData('category', value)
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {categories.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                            Priority
                                        </Label>
                                        <Select
                                            value={form.data.priority}
                                            onValueChange={(value) =>
                                                form.setData('priority', value)
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {priorities.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label
                                        htmlFor="ticket-module"
                                        className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                                    >
                                        Module or Page
                                    </Label>
                                    <Input
                                        id="ticket-module"
                                        value={form.data.module_name}
                                        onChange={(event) =>
                                            form.setData(
                                                'module_name',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Example: My Grades, Events, Question Bank"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label
                                        htmlFor="ticket-page"
                                        className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                                    >
                                        Page URL
                                    </Label>
                                    <Input
                                        id="ticket-page"
                                        value={form.data.page_url}
                                        onChange={(event) =>
                                            form.setData(
                                                'page_url',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="/analytics/exam-analytics?org=..."
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label
                                        htmlFor="ticket-details"
                                        className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                                    >
                                        Issue Details
                                    </Label>
                                    <Textarea
                                        id="ticket-details"
                                        className="min-h-40"
                                        value={form.data.details}
                                        onChange={(event) =>
                                            form.setData(
                                                'details',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Explain what happened, what you expected, and how we can reproduce it."
                                    />
                                    {form.errors.details && (
                                        <p className="text-sm text-destructive">
                                            {form.errors.details}
                                        </p>
                                    )}
                                </div>

                                <div className="rounded-[1.25rem] border border-dashed border-primary/25 bg-primary/8 p-4">
                                    <p className="font-medium text-foreground">
                                        Ticket thread is ready
                                    </p>
                                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                        After submission, you can open the
                                        ticket, add replies, and follow status
                                        changes from staff.
                                    </p>
                                    {!canCreateTicket && (
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            Ticket creation is disabled while
                                            `All Organizations` is selected.
                                        </p>
                                    )}
                                </div>

                                <Button
                                    onClick={submit}
                                    disabled={form.processing || !canCreateTicket}
                                    className="w-full border-transparent bg-[linear-gradient(135deg,#7c3aed,#c026d3)] text-white shadow-[0_18px_36px_-22px_rgb(124_58_237_/_0.58)] hover:brightness-[1.03]"
                                >
                                    <Send className="mr-2 h-4 w-4" />
                                    Submit Ticket
                                </Button>
                            </CardContent>
                        </Card>

                        <Card className="overflow-hidden border-primary/12 bg-[linear-gradient(160deg,color-mix(in_oklab,var(--color-card)_96%,var(--color-warning-soft))_0%,color-mix(in_oklab,var(--color-card)_94%,var(--color-accent))_100%)] dark:bg-[linear-gradient(160deg,color-mix(in_oklab,var(--color-card)_88%,var(--color-warning-soft))_0%,color-mix(in_oklab,var(--color-card)_94%,var(--color-accent))_100%)]">
                            <CardContent className="space-y-3 p-5">
                                <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                    Support flow
                                </p>
                                <h3 className="text-lg font-semibold text-foreground">
                                    Built for real follow-up
                                </h3>
                                <p className="text-sm leading-6 text-muted-foreground">
                                    Residents and staff can create tickets from
                                    the same entry point, while managers get a
                                    queue and status controls behind the scenes.
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    <Badge variant="outline">Create</Badge>
                                    <Badge variant="outline">Track</Badge>
                                    <Badge variant="outline">Reply</Badge>
                                    <Badge variant="outline">Resolve</Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
