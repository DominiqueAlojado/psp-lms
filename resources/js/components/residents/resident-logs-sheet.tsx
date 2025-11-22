import { ActivityLogsSheet } from '@/components/activity-logs-sheet';

interface ResidentLogsSheetProps {
    resident: {
        id: number;
        full_name: string;
        email: string;
    } | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function ResidentLogsSheet({
    resident,
    open,
    onOpenChange,
}: ResidentLogsSheetProps) {
    if (!resident) return null;

    return (
        <ActivityLogsSheet
            entity={{
                id: resident.id,
                name: resident.full_name,
                full_name: resident.full_name,
                email: resident.email,
            }}
            fetchUrl={`/residents/${resident.id}/logs`}
            title="Activity Logs"
            open={open}
            onOpenChange={onOpenChange}
        />
    );
}
