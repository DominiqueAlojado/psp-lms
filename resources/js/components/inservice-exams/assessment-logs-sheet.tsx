import { ActivityLogsSheet } from '@/components/activity-logs-sheet';

interface AssessmentLogsSheetProps {
    assessment: {
        id: number;
        title: string;
    } | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function AssessmentLogsSheet({
    assessment,
    open,
    onOpenChange,
}: AssessmentLogsSheetProps) {
    if (!assessment) return null;

    return (
        <ActivityLogsSheet
            entity={{
                id: assessment.id,
                name: assessment.title,
            }}
            fetchUrl={`/inservice-exams/${assessment.id}/logs`}
            title="Activity Logs"
            description={`Activity history for assessment: ${assessment.title}`}
            open={open}
            onOpenChange={onOpenChange}
        />
    );
}

