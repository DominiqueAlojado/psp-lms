import { ActivityLogsSheet } from '@/components/activity-logs-sheet';

interface ResourceLogsSheetProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    resource: {
        id: number;
        title: string;
    } | null;
}

export function ResourceLogsSheet({ open, onOpenChange, resource }: ResourceLogsSheetProps) {
    if (!resource) return null;

    return (
        <ActivityLogsSheet
            entity={{
                id: resource.id,
                name: resource.title,
            }}
            fetchUrl={`/resources/${resource.id}/logs`}
            title="Activity Logs"
            description={`Activity history for resource: ${resource.title}`}
            open={open}
            onOpenChange={onOpenChange}
        />
    );
}

