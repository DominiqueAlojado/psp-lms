import { ActivityLogsSheet } from '@/components/activity-logs-sheet';

interface InstitutionLogsSheetProps {
    institution: {
        id: number;
        name: string;
    } | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function InstitutionLogsSheet({
    institution,
    open,
    onOpenChange,
}: InstitutionLogsSheetProps) {
    if (!institution) return null;

    return (
        <ActivityLogsSheet
            entity={institution}
            fetchUrl={`/institutions/${institution.id}/logs`}
            title="Activity Logs"
            description={`Activity history for ${institution.name}`}
            open={open}
            onOpenChange={onOpenChange}
        />
    );
}
