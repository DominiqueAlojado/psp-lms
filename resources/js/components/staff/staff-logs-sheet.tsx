import { ActivityLogsSheet } from '@/components/activity-logs-sheet';

interface StaffLogsSheetProps {
    staff: {
        id: number;
        name: string;
        email: string;
    } | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function StaffLogsSheet({
    staff,
    open,
    onOpenChange,
}: StaffLogsSheetProps) {
    if (!staff) return null;

    return (
        <ActivityLogsSheet
            entity={staff}
            fetchUrl={`/staff/${staff.id}/logs`}
            title="Activity Logs"
            description={`Activity history for ${staff.name} (${staff.email})`}
            open={open}
            onOpenChange={onOpenChange}
        />
    );
}

