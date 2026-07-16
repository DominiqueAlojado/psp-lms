import { ActivityLogsSheet } from '@/components/activity-logs-sheet';

interface InstitutionLogsSheetProps {
    institution: {
        id: number;
        name: string;
    } | null;
    currentOrgSlug?: string | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function InstitutionLogsSheet({
    institution,
    currentOrgSlug,
    open,
    onOpenChange,
}: InstitutionLogsSheetProps) {
    if (!institution) return null;

    return (
        <ActivityLogsSheet
            entity={institution}
            fetchUrl={`/institutions/${institution.id}/logs${currentOrgSlug ? `?org=${encodeURIComponent(currentOrgSlug)}` : ''}`}
            title="Activity Logs"
            description={`Activity history for ${institution.name}`}
            open={open}
            onOpenChange={onOpenChange}
        />
    );
}
