import { ActivityLogsSheet } from '@/components/activity-logs-sheet';

interface AssignmentLogsSheetProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    assignment: {
        id: number;
        title: string;
    } | null;
}

export function AssignmentLogsSheet({ open, onOpenChange, assignment }: AssignmentLogsSheetProps) {
    if (!assignment) return null;

    return (
        <ActivityLogsSheet
            entity={{
                id: assignment.id,
                name: assignment.title,
            }}
            fetchUrl={`/assignments/${assignment.id}/logs`}
            title="Activity Logs"
            description={`Activity history for assignment: ${assignment.title}`}
            open={open}
            onOpenChange={onOpenChange}
        />
    );
}

