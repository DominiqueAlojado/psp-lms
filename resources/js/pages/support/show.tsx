import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
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
import { MessageSquareText, Plus, Send, Smile } from 'lucide-react';

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
    activityLogs: Array<{
        id: number;
        description: string;
        created_at: string;
        created_at_human: string;
        causer: {
            id: number;
            name: string;
            email: string;
        } | null;
        changes: string[];
    }>;
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

function initials(name: string | null) {
    if (!name) return 'SU';

    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

export default function SupportShow({
    ticket,
    messages,
    activityLogs,
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
        manageForm.transform((data) => ({
            ...data,
            assigned_to_user_id:
                data.assigned_to_user_id === 'unassigned'
                    ? null
                    : Number(data.assigned_to_user_id),
        }));

        manageForm.patch(preserveOrgParam(`/support/${ticket.id}`, currentOrgSlug), {
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

                                <div className="rounded-[1.25rem] border border-border/75 bg-background/88 p-5 shadow-[0_18px_34px_-30px_rgb(35_24_74_/_0.16)] dark:shadow-[0_18px_34px_-28px_rgb(0_0_0_/_0.44)]">
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
                                    <div className="space-y-5 rounded-[1.75rem] border border-border/75 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_93%,var(--color-accent)))] p-4 md:p-5">
                                        {messages.map((message) => (
                                            <div
                                                key={message.id}
                                                className="space-y-2"
                                            >
                                                <div className="flex justify-center">
                                                    <span className="rounded-full bg-muted px-3 py-1 text-[0.68rem] font-medium tracking-[0.03em] text-muted-foreground">
                                                        {message.created_at_human}
                                                    </span>
                                                </div>

                                                <div
                                                    className={`flex items-end gap-3 ${
                                                        message.is_current_user
                                                            ? 'justify-end'
                                                            : 'justify-start'
                                                    }`}
                                                >
                                                    {!message.is_current_user && (
                                                        <div className="flex size-9 shrink-0 items-center justify-center rounded-full border border-border/75 bg-background/94 text-[0.72rem] font-semibold text-foreground shadow-[0_12px_24px_-20px_rgb(35_24_74_/_0.18)] dark:shadow-[0_14px_26px_-20px_rgb(0_0_0_/_0.48)]">
                                                            {initials(
                                                                message.user.name,
                                                            )}
                                                        </div>
                                                    )}

                                                    <div
                                                        className={`flex max-w-[78%] flex-col space-y-1 ${
                                                            message.is_current_user
                                                                ? 'items-end text-right'
                                                                : ''
                                                        }`}
                                                    >
                                                        <div
                                                            className={`inline-flex w-fit max-w-full rounded-[1.5rem] px-4 py-3 text-sm leading-7 shadow-sm ${
                                                                message.is_current_user
                                                                    ? 'rounded-br-md bg-[linear-gradient(135deg,color-mix(in_oklab,var(--color-primary)_76%,#2563eb),color-mix(in_oklab,var(--color-primary)_88%,#1d4ed8))] text-white shadow-[0_20px_36px_-24px_rgb(37_99_235_/_0.48)]'
                                                                    : 'rounded-bl-md border border-border/75 bg-background/92 text-foreground'
                                                            }`}
                                                        >
                                                            <span className="break-words text-left">
                                                                {message.message}
                                                            </span>
                                                        </div>
                                                        <div
                                                            className={`px-1 text-[0.72rem] text-muted-foreground ${
                                                                message.is_current_user
                                                                    ? 'text-right'
                                                                    : ''
                                                            }`}
                                                        >
                                                            <span className="font-medium text-foreground">
                                                                {message.user.name ||
                                                                    'System'}
                                                            </span>
                                                            {message.user.email ? (
                                                                <span>
                                                                    {' '}
                                                                    ·{' '}
                                                                    {
                                                                        message.user.email
                                                                    }
                                                                </span>
                                                            ) : null}
                                                        </div>
                                                    </div>

                                                    {message.is_current_user && (
                                                        <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-[image:var(--gradient-brand)] text-[0.72rem] font-semibold text-white shadow-[0_18px_34px_-24px_rgb(124_58_237_/_0.48)]">
                                                            {initials(
                                                                message.user.name,
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                <div className="space-y-3 rounded-[1.5rem] border border-border/75 bg-background/94 p-4 shadow-[0_18px_34px_-30px_rgb(35_24_74_/_0.16)] dark:shadow-[0_18px_34px_-28px_rgb(0_0_0_/_0.4)]">
                                    <Label className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                        Add reply
                                    </Label>
                                    <div className="rounded-[1.5rem] border border-border/70 bg-muted/35 p-3">
                                        <Textarea
                                            className="min-h-28 border-0 bg-transparent px-2 py-1 shadow-none focus-visible:ring-0"
                                            value={replyForm.data.message}
                                            onChange={(event) =>
                                                replyForm.setData(
                                                    'message',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Add more context, status updates, or next steps."
                                        />
                                        <div className="mt-3 flex items-center justify-between gap-3">
                                            <div className="flex items-center gap-2">
                                                <Button
                                                    type="button"
                                                    size="icon"
                                                    variant="outline"
                                                    className="size-9 rounded-full"
                                                >
                                                    <Plus className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    size="icon"
                                                    variant="outline"
                                                    className="size-9 rounded-full"
                                                >
                                                    <Smile className="h-4 w-4" />
                                                </Button>
                                            </div>
                                            <Button
                                                onClick={reply}
                                                disabled={replyForm.processing}
                                                className="border-transparent bg-[linear-gradient(135deg,#7c3aed,#c026d3)] text-white shadow-[0_18px_36px_-22px_rgb(124_58_237_/_0.58)] hover:brightness-[1.03]"
                                            >
                                                <Send className="mr-2 h-4 w-4" />
                                                Send Reply
                                            </Button>
                                        </div>
                                    </div>
                                    {replyForm.errors.message && (
                                        <p className="text-sm text-destructive">
                                            {replyForm.errors.message}
                                        </p>
                                    )}
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

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle>Activity Timeline</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {activityLogs.length === 0 ? (
                                    <div className="rounded-[1.25rem] border border-dashed border-border/80 p-5 text-sm text-muted-foreground">
                                        No activity recorded yet.
                                    </div>
                                ) : (
                                    activityLogs.map((log) => (
                                        <div
                                            key={log.id}
                                            className="rounded-[1.25rem] border border-border/75 bg-background/88 p-4 shadow-[0_16px_30px_-28px_rgb(35_24_74_/_0.14)] dark:shadow-[0_16px_30px_-26px_rgb(0_0_0_/_0.38)]"
                                        >
                                            <div className="flex flex-col gap-2">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p className="font-medium text-foreground">
                                                            {log.description}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {log.causer
                                                                ? `${log.causer.name} (${log.causer.email})`
                                                                : 'System'}
                                                        </p>
                                                    </div>
                                                    <div className="text-right text-xs text-muted-foreground">
                                                        <p>{log.created_at_human}</p>
                                                        <p>{log.created_at}</p>
                                                    </div>
                                                </div>

                                                {log.changes.length > 0 && (
                                                    <div className="space-y-1 rounded-xl bg-muted/45 p-3 text-xs text-muted-foreground">
                                                        {log.changes.map((change, index) => (
                                                            <p key={index}>{change}</p>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))
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
                                        <InputError
                                            message={manageForm.errors.status}
                                        />
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
                                        <InputError
                                            message={manageForm.errors.priority}
                                        />
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
                                        <InputError
                                            message={
                                                manageForm.errors
                                                    .assigned_to_user_id
                                            }
                                        />
                                    </div>

                                    <InputError
                                        message={manageForm.errors.error}
                                    />

                                    <Button
                                        onClick={updateTicket}
                                        disabled={manageForm.processing}
                                        className="w-full"
                                    >
                                        {manageForm.processing
                                            ? 'Saving Changes...'
                                            : 'Save Changes'}
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
