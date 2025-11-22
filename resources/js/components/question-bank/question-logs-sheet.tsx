import { ActivityLogsSheet } from '@/components/activity-logs-sheet';

interface QuestionLogsSheetProps {
    question: {
        id: number;
        question_text: string;
    } | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function QuestionLogsSheet({
    question,
    open,
    onOpenChange,
}: QuestionLogsSheetProps) {
    if (!question) return null;

    // Truncate question text for display
    const questionPreview = question.question_text
        ? question.question_text.replace(/<[^>]*>/g, '').substring(0, 100)
        : 'Question';

    return (
        <ActivityLogsSheet
            entity={{
                id: question.id,
                name: questionPreview,
            }}
            fetchUrl={`/question-bank/${question.id}/logs`}
            title="Activity Logs"
            description={`Activity history for question #${question.id}`}
            open={open}
            onOpenChange={onOpenChange}
        />
    );
}

