import { ActivityLogsSheet } from '@/components/activity-logs-sheet';

interface EventLogsSheetProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    currentOrgSlug?: string | null;
    event: {
        id: number;
        title: string;
    } | null;
}

export function EventLogsSheet({ open, onOpenChange, event, currentOrgSlug }: EventLogsSheetProps) {
    if (!event) return null;

    return (
        <ActivityLogsSheet
            entity={{
                id: event.id,
                name: event.title,
            }}
            fetchUrl={`/events/${event.id}/logs${currentOrgSlug ? `?org=${encodeURIComponent(currentOrgSlug)}` : ''}`}
            title="Activity Logs"
            description={`Activity history for event: ${event.title}`}
            open={open}
            onOpenChange={onOpenChange}
        />
    );
}
