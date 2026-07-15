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
import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Clock3,
    LifeBuoy,
    MessageSquareText,
    Send,
    Sparkles,
    Wrench,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Support',
        href: '/support',
    },
];

type TicketStatus = 'open' | 'in_review' | 'resolved';
type TicketPriority = 'low' | 'medium' | 'high';
type TicketCategory = 'bug' | 'billing' | 'content' | 'account' | 'feature';

interface Ticket {
    id: string;
    title: string;
    category: TicketCategory;
    priority: TicketPriority;
    status: TicketStatus;
    updatedAt: string;
    summary: string;
}

const initialTickets: Ticket[] = [
    {
        id: 'SUP-1042',
        title: 'Unable to open one resident exam result page',
        category: 'bug',
        priority: 'high',
        status: 'in_review',
        updatedAt: '10 minutes ago',
        summary: 'Results page loads, but one attempt shows a blank panel after submission.',
    },
    {
        id: 'SUP-1038',
        title: 'Request to improve grade breakdown clarity',
        category: 'feature',
        priority: 'medium',
        status: 'open',
        updatedAt: '2 hours ago',
        summary: 'Resident asked for clearer topic-level explanations in the grades section.',
    },
    {
        id: 'SUP-1017',
        title: 'Announcement formatting issue resolved',
        category: 'content',
        priority: 'low',
        status: 'resolved',
        updatedAt: 'Yesterday',
        summary: 'Long announcement text was overflowing on mobile and has already been corrected.',
    },
];

const categoryOptions: Array<{
    value: TicketCategory;
    label: string;
}> = [
    { value: 'bug', label: 'Bug or Error' },
    { value: 'billing', label: 'Billing or Subscription' },
    { value: 'content', label: 'Content or Data Issue' },
    { value: 'account', label: 'Account Access' },
    { value: 'feature', label: 'Feature Request' },
];

const priorityOptions: Array<{
    value: TicketPriority;
    label: string;
}> = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
];

function statusBadge(status: TicketStatus) {
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

function priorityBadge(priority: TicketPriority) {
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
            return (
                <Badge variant="outline">Low</Badge>
            );
    }
}

export default function SupportIndex() {
    const page = usePage<SharedData>();
    const currentOrgSlug = page.props.auth.currentOrganization?.slug;
    const [tickets, setTickets] = useState<Ticket[]>(initialTickets);
    const [title, setTitle] = useState('');
    const [category, setCategory] = useState<TicketCategory>('bug');
    const [priority, setPriority] = useState<TicketPriority>('medium');
    const [moduleName, setModuleName] = useState('');
    const [details, setDetails] = useState('');

    const openCount = useMemo(
        () => tickets.filter((ticket) => ticket.status === 'open').length,
        [tickets],
    );
    const reviewCount = useMemo(
        () => tickets.filter((ticket) => ticket.status === 'in_review').length,
        [tickets],
    );
    const resolvedCount = useMemo(
        () => tickets.filter((ticket) => ticket.status === 'resolved').length,
        [tickets],
    );

    const handleSubmit = () => {
        if (!title.trim() || !details.trim()) {
            toast.error('Please add a ticket title and issue details.');
            return;
        }

        const nextTicket: Ticket = {
            id: `SUP-${1000 + tickets.length + 51}`,
            title: title.trim(),
            category,
            priority,
            status: 'open',
            updatedAt: 'Just now',
            summary: moduleName.trim()
                ? `${moduleName.trim()}: ${details.trim()}`
                : details.trim(),
        };

        setTickets((current) => [nextTicket, ...current]);
        setTitle('');
        setCategory('bug');
        setPriority('medium');
        setModuleName('');
        setDetails('');
        toast.success('Support ticket created in the preview module.');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Customer Support" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <HeadingSmall
                        title="Customer Support"
                        description="Create tickets, track issue progress, and keep residents and staff supported in one place."
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button
                            asChild
                            variant="outline"
                            className="border-primary/20 text-primary"
                        >
                            <Link href={preserveOrgParam('/feedback', currentOrgSlug)}>
                                <MessageSquareText className="mr-2 h-4 w-4" />
                                Leave Feedback
                            </Link>
                        </Button>
                        <Button
                            onClick={handleSubmit}
                            className="border-transparent bg-[linear-gradient(135deg,#7c3aed,#c026d3)] text-white shadow-[0_18px_36px_-22px_rgb(124_58_237_/_0.58)] hover:brightness-[1.03]"
                        >
                            <Send className="mr-2 h-4 w-4" />
                            Create Ticket
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="All Tickets"
                        value={tickets.length}
                        description="Preview queue across support requests"
                        icon={LifeBuoy}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Open"
                        value={openCount}
                        description="New issues waiting for triage"
                        icon={AlertTriangle}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="In Review"
                        value={reviewCount}
                        description="Requests currently being investigated"
                        icon={Clock3}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Resolved"
                        value={resolvedCount}
                        description="Tickets already completed"
                        icon={CheckCircle2}
                        iconColor="text-primary"
                    />
                </div>

                <Card className="overflow-hidden border-primary/10 bg-[linear-gradient(135deg,rgba(248,244,255,0.98),rgba(255,255,255,0.94))]">
                    <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Support hub
                            </p>
                            <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                Same calm layout, but built for issue reporting
                            </h3>
                            <p className="text-sm leading-6 text-muted-foreground">
                                This support space mirrors the polished SaaS feel from your reference while giving users a clean place to submit and review issues.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">Design + interactive preview</Badge>
                            <Badge variant="outline">Backend persistence can be added next</Badge>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <div className="space-y-6">
                        <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                            <CardHeader className="pb-3">
                                <CardTitle>Recent Tickets</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {tickets.map((ticket) => (
                                    <div
                                        key={ticket.id}
                                        className="rounded-[1.35rem] border border-border/70 bg-background/85 p-5"
                                    >
                                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                            <div className="space-y-2">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Badge variant="outline">{ticket.id}</Badge>
                                                    {statusBadge(ticket.status)}
                                                    {priorityBadge(ticket.priority)}
                                                </div>
                                                <h3 className="text-base font-semibold text-foreground">
                                                    {ticket.title}
                                                </h3>
                                                <p className="text-sm leading-6 text-muted-foreground">
                                                    {ticket.summary}
                                                </p>
                                            </div>
                                            <div className="flex shrink-0 flex-col items-start gap-2 lg:items-end">
                                                <Badge className="border-transparent bg-primary/10 text-primary">
                                                    {
                                                        categoryOptions.find(
                                                            (option) =>
                                                                option.value ===
                                                                ticket.category,
                                                        )?.label
                                                    }
                                                </Badge>
                                                <span className="text-xs font-medium text-muted-foreground">
                                                    Updated {ticket.updatedAt}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                            <CardHeader className="pb-3">
                                <CardTitle>Suggested Ticket Flow</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-3">
                                <div className="rounded-[1.25rem] border border-border/70 bg-background/85 p-4">
                                    <div className="mb-3 flex size-10 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                        <AlertTriangle className="size-4" />
                                    </div>
                                    <h3 className="font-semibold text-foreground">
                                        1. Report
                                    </h3>
                                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                        User reports a bug, blocker, or account issue.
                                    </p>
                                </div>
                                <div className="rounded-[1.25rem] border border-border/70 bg-background/85 p-4">
                                    <div className="mb-3 flex size-10 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                        <Wrench className="size-4" />
                                    </div>
                                    <h3 className="font-semibold text-foreground">
                                        2. Triage
                                    </h3>
                                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                        Support reviews the issue and updates the ticket status.
                                    </p>
                                </div>
                                <div className="rounded-[1.25rem] border border-border/70 bg-background/85 p-4">
                                    <div className="mb-3 flex size-10 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                        <Sparkles className="size-4" />
                                    </div>
                                    <h3 className="font-semibold text-foreground">
                                        3. Resolve
                                    </h3>
                                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                        The team closes the ticket and can follow up with feedback later.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
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
                                        value={title}
                                        onChange={(event) =>
                                            setTitle(event.target.value)
                                        }
                                        placeholder="Briefly describe the issue"
                                    />
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                            Category
                                        </Label>
                                        <Select
                                            value={category}
                                            onValueChange={(value) =>
                                                setCategory(
                                                    value as TicketCategory,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {categoryOptions.map((option) => (
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
                                            value={priority}
                                            onValueChange={(value) =>
                                                setPriority(
                                                    value as TicketPriority,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {priorityOptions.map((option) => (
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
                                        value={moduleName}
                                        onChange={(event) =>
                                            setModuleName(
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Example: My Grades, Events, Question Bank"
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
                                        value={details}
                                        onChange={(event) =>
                                            setDetails(
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Explain what happened, what you expected, and any steps to reproduce the issue."
                                    />
                                </div>

                                <div className="rounded-[1.25rem] border border-dashed border-primary/20 bg-primary/5 p-4">
                                    <p className="font-medium text-foreground">
                                        Future-ready support flow
                                    </p>
                                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                        This first version is interactive on the frontend. Next we can connect it to database-backed tickets, assignment rules, staff replies, and email notifications.
                                    </p>
                                </div>

                                <Button
                                    onClick={handleSubmit}
                                    className="w-full border-transparent bg-[linear-gradient(135deg,#7c3aed,#c026d3)] text-white shadow-[0_18px_36px_-22px_rgb(124_58_237_/_0.58)] hover:brightness-[1.03]"
                                >
                                    <Send className="mr-2 h-4 w-4" />
                                    Submit Ticket
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
