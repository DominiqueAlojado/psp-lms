import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { preserveOrgParam } from '@/lib/utils';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck, CheckCircle2, Inbox } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notifications',
        href: '/notifications',
    },
];

interface NotificationItem {
    id: string;
    title: string;
    message: string;
    category: string;
    event: string;
    url: string;
    ticket_number: string | null;
    organization_name: string | null;
    actor_name: string | null;
    is_read: boolean;
    created_at: string;
    created_at_human: string;
}

interface PageProps {
    summary: {
        total: number;
        unread: number;
        read: number;
    };
    notificationFeed: {
        data: NotificationItem[];
        current_page: number;
        last_page: number;
    };
}

export default function NotificationsIndex({ summary, notificationFeed }: PageProps) {
    const page = usePage<SharedData>();
    const currentOrgSlug = page.props.auth.currentOrganization?.slug;

    const openNotification = (notificationId: string, targetUrl: string) => {
        router.post(
            preserveOrgParam(`/notifications/${notificationId}/read`, currentOrgSlug),
            { redirect_to: targetUrl },
            { preserveScroll: true },
        );
    };

    const markAsRead = (notificationId: string) => {
        router.post(
            preserveOrgParam(`/notifications/${notificationId}/read`, currentOrgSlug),
            {},
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const markAllAsRead = () => {
        router.post(
            preserveOrgParam('/notifications/read-all', currentOrgSlug),
            {},
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifications" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <HeadingSmall
                        title="Notifications"
                        description="Track support updates and other system messages without leaving the active workflow."
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={preserveOrgParam('/support', currentOrgSlug)}>
                                Open Support
                            </Link>
                        </Button>
                        {summary.unread > 0 && (
                            <Button onClick={markAllAsRead}>
                                <CheckCheck className="mr-2 h-4 w-4" />
                                Mark all read
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <StatCard title="Total" value={summary.total} description="All notifications" icon={Inbox} />
                    <StatCard title="Unread" value={summary.unread} description="Needs attention" icon={Bell} />
                    <StatCard title="Read" value={summary.read} description="Already reviewed" icon={CheckCircle2} />
                </div>

                <Card className="border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))]">
                    <CardHeader className="pb-4">
                        <CardTitle>Recent Activity</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {notificationFeed.data.length === 0 ? (
                            <div className="rounded-[1.25rem] border border-dashed border-border/80 p-8 text-center">
                                <p className="font-medium text-foreground">No notifications yet</p>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    New support updates will show here once staff or users reply.
                                </p>
                            </div>
                        ) : (
                            notificationFeed.data.map((notification) => (
                                <div
                                    key={notification.id}
                                    className={`rounded-[1.25rem] border p-5 ${
                                        notification.is_read
                                            ? 'border-border/75 bg-background/88'
                                            : 'border-primary/25 bg-primary/8 shadow-[0_18px_34px_-30px_rgb(96_44_193_/_0.18)] dark:shadow-[0_18px_34px_-28px_rgb(59_27_135_/_0.32)]'
                                    }`}
                                >
                                    <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                {!notification.is_read && (
                                                    <Badge className="border-transparent bg-primary/12 text-primary">
                                                        Unread
                                                    </Badge>
                                                )}
                                                {notification.ticket_number && (
                                                    <Badge variant="outline">
                                                        {notification.ticket_number}
                                                    </Badge>
                                                )}
                                                {notification.organization_name && (
                                                    <Badge variant="outline">
                                                        {notification.organization_name}
                                                    </Badge>
                                                )}
                                            </div>
                                            <h3 className="text-base font-semibold text-foreground">
                                                {notification.title}
                                            </h3>
                                            <p className="text-sm leading-6 text-muted-foreground">
                                                {notification.message}
                                            </p>
                                            <div className="flex flex-wrap gap-3 text-xs text-muted-foreground">
                                                <span>{notification.created_at_human}</span>
                                                {notification.actor_name && (
                                                    <span>By {notification.actor_name}</span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex shrink-0 flex-wrap gap-2">
                                            {!notification.is_read && (
                                                <Button
                                                    variant="outline"
                                                    onClick={() => markAsRead(notification.id)}
                                                >
                                                    Mark Read
                                                </Button>
                                            )}
                                            <Button
                                                onClick={() =>
                                                    openNotification(
                                                        notification.id,
                                                        notification.url,
                                                    )
                                                }
                                            >
                                                Open
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}

                        {notificationFeed.last_page > 1 && (
                            <div className="flex flex-wrap items-center justify-center gap-2 pt-2">
                                {Array.from({ length: notificationFeed.last_page }, (_, index) => index + 1).map(
                                    (pageNumber) => (
                                        <Button
                                            key={pageNumber}
                                            size="sm"
                                            variant={
                                                pageNumber === notificationFeed.current_page
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            onClick={() =>
                                                router.get(
                                                    preserveOrgParam('/notifications', currentOrgSlug),
                                                    { page: pageNumber },
                                                    {
                                                        preserveState: true,
                                                        preserveScroll: true,
                                                    },
                                                )
                                            }
                                        >
                                            {pageNumber}
                                        </Button>
                                    ),
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
