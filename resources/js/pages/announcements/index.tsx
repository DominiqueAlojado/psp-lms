import HeadingSmall from '@/components/heading-small';
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
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import { AlertCircle, Eye, Megaphone, Pin, Settings } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Announcements',
        href: '/announcements',
    },
];

interface Announcement {
    id: number;
    title: string;
    content: string;
    scope: 'organization' | 'system';
    priority: 'normal' | 'important' | 'urgent';
    is_pinned: boolean;
    target_year_levels: string[] | null;
    expires_at: string | null;
    organization_name: string | null;
    created_by: string;
    created_at: string;
    views_count: number;
}

interface PaginatedAnnouncements {
    data: Announcement[];
    total: number;
    current_page: number;
    last_page: number;
}

interface PageProps {
    announcements: PaginatedAnnouncements;
    filters: {
        priority?: string;
    };
    [key: string]: unknown;
}

const priorityColors = {
    normal: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    important:
        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    urgent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

const priorityIcons = {
    normal: Megaphone,
    important: AlertCircle,
    urgent: AlertCircle,
};

export default function AnnouncementsIndex() {
    const { announcements } = usePage<PageProps>().props;
    const { hasPermission } = usePermissions();
    const canManage = hasPermission('create-announcements');
    const [selectedAnnouncement, setSelectedAnnouncement] =
        useState<Announcement | null>(null);

    const getPreviewText = (htmlContent: string, maxLength = 150) => {
        const text = htmlContent.replace(/<[^>]*>/g, '');
        return text.length > maxLength
            ? text.substring(0, maxLength) + '...'
            : text;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Announcements" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Announcements"
                        description="Stay updated with the latest news and updates"
                    />
                    {canManage && (
                        <Button asChild>
                            <Link href="/announcements/manage">
                                <Settings className="mr-2 h-4 w-4" />
                                Manage
                            </Link>
                        </Button>
                    )}
                </div>

                {/* Announcements List */}
                {announcements.data.length === 0 ? (
                    <Card>
                        <CardContent className="p-12 text-center">
                            <Megaphone className="mx-auto h-12 w-12 text-muted-foreground" />
                            <p className="mt-4 text-sm text-muted-foreground">
                                No announcements at this time
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {announcements.data.map((announcement) => {
                            const PriorityIcon =
                                priorityIcons[announcement.priority];

                            return (
                                <Card
                                    key={announcement.id}
                                    className={`${
                                        announcement.is_pinned
                                            ? 'border-l-4 border-l-primary'
                                            : ''
                                    } ${
                                        announcement.priority === 'urgent'
                                            ? 'border-2 border-red-200 dark:border-red-900/50'
                                            : ''
                                    }`}
                                >
                                    <CardContent className="p-6">
                                        <div className="flex items-start gap-4">
                                            <div
                                                className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${
                                                    priorityColors[
                                                        announcement.priority
                                                    ]
                                                }`}
                                            >
                                                <PriorityIcon className="h-5 w-5" />
                                            </div>

                                            <div className="flex-1 space-y-2">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    {announcement.is_pinned && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="gap-1"
                                                        >
                                                            <Pin className="h-3 w-3" />
                                                            Pinned
                                                        </Badge>
                                                    )}
                                                    {announcement.priority !==
                                                        'normal' && (
                                                        <Badge
                                                            variant={
                                                                announcement.priority ===
                                                                'urgent'
                                                                    ? 'destructive'
                                                                    : 'default'
                                                            }
                                                        >
                                                            {announcement.priority.toUpperCase()}
                                                        </Badge>
                                                    )}
                                                    {announcement.scope ===
                                                        'system' && (
                                                        <Badge variant="outline">
                                                            System-wide
                                                        </Badge>
                                                    )}
                                                    {announcement.organization_name && (
                                                        <Badge variant="outline">
                                                            {
                                                                announcement.organization_name
                                                            }
                                                        </Badge>
                                                    )}
                                                </div>

                                                <h3 className="text-xl font-semibold">
                                                    {announcement.title}
                                                </h3>

                                                <p className="text-sm text-muted-foreground">
                                                    {getPreviewText(
                                                        announcement.content,
                                                    )}
                                                </p>

                                                <Button
                                                    variant="link"
                                                    className="h-auto p-0 text-primary"
                                                    onClick={() => {
                                                        setSelectedAnnouncement(
                                                            announcement,
                                                        );
                                                        // Track view silently in background
                                                        axios
                                                            .post(
                                                                `/announcements/${announcement.id}/view`,
                                                            )
                                                            .catch((err) =>
                                                                console.error(
                                                                    'Failed to track view:',
                                                                    err,
                                                                ),
                                                            );
                                                    }}
                                                >
                                                    <Eye className="mr-1 h-4 w-4" />
                                                    Read more
                                                </Button>

                                                <div className="flex flex-wrap items-center gap-3 pt-2 text-sm text-muted-foreground">
                                                    <span>
                                                        Posted by{' '}
                                                        {
                                                            announcement.created_by
                                                        }
                                                    </span>
                                                    <span>•</span>
                                                    <span>
                                                        {
                                                            announcement.created_at
                                                        }
                                                    </span>
                                                    {announcement.expires_at && (
                                                        <>
                                                            <span>•</span>
                                                            <span>
                                                                Expires on{' '}
                                                                {
                                                                    announcement.expires_at
                                                                }
                                                            </span>
                                                        </>
                                                    )}
                                                </div>

                                                {announcement.target_year_levels &&
                                                    announcement
                                                        .target_year_levels
                                                        .length > 0 && (
                                                        <div className="flex flex-wrap gap-2 pt-2">
                                                            <span className="text-sm text-muted-foreground">
                                                                Target:
                                                            </span>
                                                            {announcement.target_year_levels.map(
                                                                (level) => (
                                                                    <Badge
                                                                        key={
                                                                            level
                                                                        }
                                                                        variant="secondary"
                                                                    >
                                                                        {level}
                                                                    </Badge>
                                                                ),
                                                            )}
                                                        </div>
                                                    )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}

                {/* View Announcement Dialog */}
                {selectedAnnouncement && (
                    <Dialog
                        open={selectedAnnouncement !== null}
                        onOpenChange={(open) =>
                            !open && setSelectedAnnouncement(null)
                        }
                    >
                        <DialogContent className="max-h-[90vh] w-[95vw] max-w-3xl overflow-y-auto">
                            <DialogHeader>
                                <div className="flex flex-wrap items-center gap-2">
                                    {selectedAnnouncement.is_pinned && (
                                        <Badge
                                            variant="secondary"
                                            className="gap-1"
                                        >
                                            <Pin className="h-3 w-3" />
                                            Pinned
                                        </Badge>
                                    )}
                                    {selectedAnnouncement.priority !==
                                        'normal' && (
                                        <Badge
                                            variant={
                                                selectedAnnouncement.priority ===
                                                'urgent'
                                                    ? 'destructive'
                                                    : 'default'
                                            }
                                        >
                                            {selectedAnnouncement.priority.toUpperCase()}
                                        </Badge>
                                    )}
                                    {selectedAnnouncement.scope ===
                                        'system' && (
                                        <Badge variant="outline">
                                            System-wide
                                        </Badge>
                                    )}
                                    {selectedAnnouncement.organization_name && (
                                        <Badge variant="outline">
                                            {
                                                selectedAnnouncement.organization_name
                                            }
                                        </Badge>
                                    )}
                                </div>
                                <DialogTitle className="text-2xl">
                                    {selectedAnnouncement.title}
                                </DialogTitle>
                                <DialogDescription className="flex flex-wrap items-center gap-2 text-sm">
                                    <span>
                                        Posted by{' '}
                                        {selectedAnnouncement.created_by}
                                    </span>
                                    <span>•</span>
                                    <span>
                                        {selectedAnnouncement.created_at}
                                    </span>
                                    {selectedAnnouncement.expires_at && (
                                        <>
                                            <span>•</span>
                                            <span>
                                                Expires on{' '}
                                                {
                                                    selectedAnnouncement.expires_at
                                                }
                                            </span>
                                        </>
                                    )}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                <div
                                    className="prose prose-sm dark:prose-invert max-w-none"
                                    dangerouslySetInnerHTML={{
                                        __html: selectedAnnouncement.content,
                                    }}
                                />

                                {selectedAnnouncement.target_year_levels &&
                                    selectedAnnouncement.target_year_levels
                                        .length > 0 && (
                                        <div className="flex flex-wrap gap-2 border-t pt-4">
                                            <span className="text-sm font-medium">
                                                Target Year Levels:
                                            </span>
                                            {selectedAnnouncement.target_year_levels.map(
                                                (level) => (
                                                    <Badge
                                                        key={level}
                                                        variant="secondary"
                                                    >
                                                        {level}
                                                    </Badge>
                                                ),
                                            )}
                                        </div>
                                    )}
                            </div>
                        </DialogContent>
                    </Dialog>
                )}
            </div>
        </AppLayout>
    );
}
