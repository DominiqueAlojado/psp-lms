import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { MessageSquareText, Send } from 'lucide-react';

interface Option {
    value: string;
    label: string;
}

interface MessageItem {
    id: number;
    message: string;
    created_at: string;
    created_at_human: string;
    user: {
        id: number | null;
        name: string | null;
        email: string | null;
    };
    is_current_user: boolean;
}

interface PageProps {
    ticket: {
        id: number;
        ticket_number: string;
        title: string;
        category: string;
        priority: string;
        status: string;
        module_name: string | null;
        page_url: string | null;
        details: string;
        organization_name: string | null;
        creator: {
            name: string | null;
            email: string | null;
        };
        assignee: {
            id: number;
            name: string;
            email: string;
        } | null;
        created_at: string;
        updated_at_human: string;
        resolved_at: string | null;
    };
    messages: MessageItem[];
    canManage: boolean;
    priorities: Option[];
    statuses: Option[];
    assignees: Array<{
        id: number;
        name: string;
        email: string;
    }>;
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

export default function SupportShow({
    ticket,
    messages,
    canManage,
    priorities,
    statuses,
    assignees,
}: PageProps) {
    const page = usePage<SharedData>();
    const currentOrgSlug = page.props.auth.currentOrganization?.slug;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Support', href: '/support' },
        { title: ticket.ticket_number, href: `/support/${ticket.id}` },
    ];

    const replyForm = useForm({
        message: '',
    });

    const manageForm = useForm({
        status: ticket.status,
        priority: ticket.priority,
        assigned_to_user_id: ticket.assignee?.id
            ? String(ticket.assignee.id)
            : 'unassigned',
    });

    const reply = () => {
        replyForm.post(
            preserveOrgParam(`/support/${ticket.id}/messages`, currentOrgSlug),
            {
                preserveScroll: true,
                onSuccess: () => replyForm.reset('message'),
            },
        );
    };

    const updateTicket = () => {
        manageForm
            .transform((data) => ({
                ...data,
                assigned_to_user_id:
                    data.assigned_to_user_id === 'unassigned'
                        ? null
                        : Number(data.assigned_to_user_id),
            }))
            .patch(preserveOrgParam(`/support/${ticket.id}`, currentOrgSlug), {
                preserveScroll: true,
            });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={ticket.ticket_number} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <HeadingSmall
                        title={ticket.title}
                        description="Review the ticket context, follow the reply thread, and keep the support conversation moving."
                    />
                    <div className="flex flex-wrap gap-2">
                        {canManage && (
                            <Button asChild variant="outline">
                                <Link
                                    href={preserveOrgParam(
                                        '/support/manage',
                                        currentOrgSlug,
                                    )}
                                >
                                    Back to Queue
                                </Link>
                            </Button>
                        )}
                        {!canManage && (
                            <Button asChild variant="outline">
                                <Link
                                    href={preserveOrgParam('/support', currentOrgSlug)}
                                >
                                    Back to My Tickets
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader className="pb-3">
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="outline">
                                        {ticket.ticket_number}
                                    </Badge>
                                    {statusBadge(ticket.status)}
                                    {priorityBadge(ticket.priority)}
                                    <Badge variant="outline">
                                        {ticket.category.replace('_', ' ')}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 text-sm text-muted-foreground md:grid-cols-2">
                                    <div>
                                        <p className="font-medium text-foreground">
                                            Requested by
                                        </p>
                                        <p>{ticket.creator.name}</p>
                                        <p>{ticket.creator.email}</p>
                                    </div>
                                    <div>
                                        <p className="font-medium text-foreground">
                                            Organization
                                        </p>
                                        <p>{ticket.organization_name || 'Not specified'}</p>
                                    </div>
                                    <div>
                                        <p className="font-medium text-foreground">
                                            Module
                                        </p>
                                        <p>{ticket.module_name || 'Not specified'}</p>
                                    </div>
                                    <div>
                                        <p className="font-medium text-foreground">
                                            Page
                                        </p>
                                        <p className="break-all">
                                            {ticket.page_url || 'Not specified'}
                                        </p>
                                    </div>
                                </div>

                                <div className="rounded-[1.25rem] border border-border/70 bg-background/85 p-5">
                                    <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                        Initial issue details
                                    </p>
                                    <p className="mt-3 text-sm leading-7 text-foreground">
                                        {ticket.details}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2">
                                    <MessageSquareText className="h-4 w-4" />
                                    Conversation
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {messages.length === 0 ? (
                                    <div className="rounded-[1.25rem] border border-dashed border-border/80 p-6 text-center">
                                        <p className="font-medium text-foreground">
                                            No replies yet
                                        </p>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            Use the reply box below to add more
                                            context or provide an update.
                                        </p>
                                    </div>
                                ) : (
                                    messages.map((message) => (
                                        <div
                                            key={message.id}
                                            className={`rounded-[1.25rem] border p-4 ${
                                                message.is_current_user
                                                    ? 'border-primary/20 bg-primary/5'
                                                    : 'border-border/70 bg-background/85'
                                            }`}
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <div>
                                                    <p className="font-medium text-foreground">
                                                        {message.user.name || 'System'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {message.user.email}
                                                    </p>
                                                </div>
                                                <p className="text-xs text-muted-foreground">
                                                    {message.created_at_human}
                                                </p>
                                            </div>
                                            <p className="mt-3 text-sm leading-7 text-foreground">
                                                {message.message}
                                            </p>
                                        </div>
                                    ))
                                )}

                                <div className="space-y-3 rounded-[1.25rem] border border-border/70 bg-background/85 p-4">
                                    <Label className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                        Add reply
                                    </Label>
                                    <Textarea
                                        className="min-h-32"
                                        value={replyForm.data.message}
                                        onChange={(event) =>
                                            replyForm.setData(
                                                'message',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Add more context, status updates, or next steps."
                                    />
                                    {replyForm.errors.message && (
                                        <p className="text-sm text-destructive">
                                            {replyForm.errors.message}
                                        </p>
                                    )}
                                    <Button
                                        onClick={reply}
                                        disabled={replyForm.processing}
                                    >
                                        <Send className="mr-2 h-4 w-4" />
                                        Send Reply
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle>Ticket State</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm text-muted-foreground">
                                <p>Created {ticket.created_at}</p>
                                <p>Updated {ticket.updated_at_human}</p>
                                {ticket.assignee && (
                                    <p>Assigned to {ticket.assignee.name}</p>
                                )}
                                {ticket.resolved_at && (
                                    <p>Resolved {ticket.resolved_at}</p>
                                )}
                            </CardContent>
                        </Card>

                        {canManage && (
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle>Manage Ticket</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label>Status</Label>
                                        <Select
                                            value={manageForm.data.status}
                                            onValueChange={(value) =>
                                                manageForm.setData(
                                                    'status',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
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

                                    <div className="space-y-2">
                                        <Label>Priority</Label>
                                        <Select
                                            value={manageForm.data.priority}
                                            onValueChange={(value) =>
                                                manageForm.setData(
                                                    'priority',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {priorities.map((priority) => (
                                                    <SelectItem
                                                        key={priority.value}
                                                        value={priority.value}
                                                    >
                                                        {priority.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label>Assignee</Label>
                                        <Select
                                            value={manageForm.data.assigned_to_user_id}
                                            onValueChange={(value) =>
                                                manageForm.setData(
                                                    'assigned_to_user_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="unassigned">
                                                    Unassigned
                                                </SelectItem>
                                                {assignees.map((assignee) => (
                                                    <SelectItem
                                                        key={assignee.id}
                                                        value={String(assignee.id)}
                                                    >
                                                        {assignee.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <Button
                                        onClick={updateTicket}
                                        disabled={manageForm.processing}
                                        className="w-full"
                                    >
                                        Save Changes
                                    </Button>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
