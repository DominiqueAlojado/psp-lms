import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ScrollArea } from '@/components/ui/scroll-area';
import { preserveOrgParam } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck, ExternalLink } from 'lucide-react';

export function NotificationCenter() {
    const page = usePage<SharedData>();
    const currentOrgSlug = page.props.auth.currentOrganization?.slug;
    const notifications = page.props.notifications ?? { unreadCount: 0, latest: [] };
    const latestNotifications = notifications.latest ?? [];

    const markAllAsRead = () => {
        router.post(preserveOrgParam('/notifications/read-all', currentOrgSlug), {}, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const openNotification = (notificationId: string, targetUrl: string) => {
        router.post(
            preserveOrgParam(`/notifications/${notificationId}/read`, currentOrgSlug),
            { redirect_to: targetUrl },
            { preserveScroll: true },
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative rounded-2xl border border-border/70 bg-background/80 shadow-[0_12px_28px_-24px_rgb(35_24_74_/_0.28)]"
                >
                    <Bell className="size-4" />
                    {notifications.unreadCount > 0 && (
                        <span className="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[11px] font-semibold text-white">
                            {notifications.unreadCount > 9 ? '9+' : notifications.unreadCount}
                        </span>
                    )}
                    <span className="sr-only">Notifications</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                className="w-[380px] rounded-2xl border-border/75 p-0 shadow-[0_24px_52px_-34px_rgb(35_24_74_/_0.32)]"
            >
                <div className="flex items-center justify-between border-b border-border/70 px-4 py-3">
                    <div>
                        <p className="font-semibold text-foreground">Notifications</p>
                        <p className="text-xs text-muted-foreground">
                            {notifications.unreadCount} unread
                        </p>
                    </div>
                    {notifications.unreadCount > 0 && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-8 px-2 text-xs"
                            onClick={markAllAsRead}
                        >
                            <CheckCheck className="mr-1 h-3.5 w-3.5" />
                            Mark all read
                        </Button>
                    )}
                </div>

                <ScrollArea className="max-h-[420px]">
                    <div className="space-y-2 p-3">
                        {latestNotifications.length === 0 ? (
                            <div className="rounded-[1.1rem] border border-dashed border-border/70 px-4 py-8 text-center">
                                <p className="font-medium text-foreground">No notifications yet</p>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    Support updates and system activity will appear here.
                                </p>
                            </div>
                        ) : (
                            latestNotifications.map((notification) => (
                                <button
                                    key={notification.id}
                                    type="button"
                                    onClick={() =>
                                        openNotification(notification.id, notification.url)
                                    }
                                    className={`w-full rounded-[1.1rem] border p-3 text-left transition hover:bg-accent/40 ${
                                        notification.is_read
                                            ? 'border-border/70 bg-background/70'
                                            : 'border-primary/20 bg-primary/5'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="space-y-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                {!notification.is_read && (
                                                    <Badge className="border-transparent bg-primary/12 text-primary">
                                                        New
                                                    </Badge>
                                                )}
                                                {notification.ticket_number && (
                                                    <Badge variant="outline">
                                                        {notification.ticket_number}
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="font-medium text-foreground">
                                                {notification.title}
                                            </p>
                                            <p className="text-sm leading-6 text-muted-foreground">
                                                {notification.message}
                                            </p>
                                            <div className="flex flex-wrap gap-2 text-xs text-muted-foreground">
                                                {notification.organization_name && (
                                                    <span>{notification.organization_name}</span>
                                                )}
                                                <span>{notification.created_at_human}</span>
                                            </div>
                                        </div>
                                        <ExternalLink className="mt-1 h-4 w-4 shrink-0 text-muted-foreground" />
                                    </div>
                                </button>
                            ))
                        )}
                    </div>
                </ScrollArea>

                <div className="border-t border-border/70 p-3">
                    <Button asChild variant="outline" className="w-full">
                        <Link href={preserveOrgParam('/notifications', currentOrgSlug)}>
                            View all notifications
                        </Link>
                    </Button>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
