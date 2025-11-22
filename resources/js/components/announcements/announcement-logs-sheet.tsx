import { ActivityLogsSheet } from '@/components/activity-logs-sheet';

interface AnnouncementLogsSheetProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    announcement: {
        id: number;
        title: string;
    } | null;
}

export function AnnouncementLogsSheet({
    open,
    onOpenChange,
    announcement,
}: AnnouncementLogsSheetProps) {
    if (!announcement) return null;

    return (
        <ActivityLogsSheet
            entity={{
                id: announcement.id,
                name: announcement.title,
            }}
            fetchUrl={`/announcements/${announcement.id}/logs`}
            title="Activity Logs"
            description={`Activity history for announcement: ${announcement.title}`}
            open={open}
            onOpenChange={onOpenChange}
        />
    );
}

