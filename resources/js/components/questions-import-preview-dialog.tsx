import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { AlertCircle, Check, FileSpreadsheet, X } from 'lucide-react';

interface ParsedQuestion {
    row_number: number;
    question_text: string;
    type: string;
    points: number;
    choices: Array<{
        text: string;
        is_correct: boolean;
    }>;
    explanation: string | null;
    topic: {
        name: string;
        exists: boolean;
        will_create: boolean;
    } | null;
}

interface Props {
    open: boolean;
    questions: ParsedQuestion[];
    errors: string[];
    totalValid: number;
    totalErrors: number;
    onConfirm: () => void;
    onCancel: () => void;
    isImporting?: boolean;
}

const typeLabels: Record<string, string> = {
    multiple_choice: 'Multiple Choice',
    multiple_select: 'Multiple Select',
    true_false: 'True/False',
};

export function QuestionsImportPreviewDialog({
    open,
    questions,
    errors,
    totalValid,
    totalErrors,
    onConfirm,
    onCancel,
    isImporting = false,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onCancel()}>
            <DialogContent className="max-w-4xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <FileSpreadsheet className="h-5 w-5" />
                        Import Preview
                    </DialogTitle>
                    <DialogDescription>
                        Review the questions before importing. {totalValid} valid questions found
                        {totalErrors > 0 && `, ${totalErrors} errors detected`}.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    {/* Summary */}
                    <div className="flex gap-4 rounded-lg bg-muted/50 p-4">
                        <div className="flex items-center gap-2">
                            <Check className="h-5 w-5 text-green-600" />
                            <span className="font-medium">{totalValid} Valid</span>
                        </div>
                        {totalErrors > 0 && (
                            <div className="flex items-center gap-2">
                                <X className="h-5 w-5 text-destructive" />
                                <span className="font-medium">{totalErrors} Errors</span>
                            </div>
                        )}
                    </div>

                    {/* Errors */}
                    {errors.length > 0 && (
                        <div className="space-y-2">
                            <h4 className="flex items-center gap-2 text-sm font-semibold text-destructive">
                                <AlertCircle className="h-4 w-4" />
                                Errors Found
                            </h4>
                            <div className="max-h-32 space-y-1 overflow-y-auto rounded-lg bg-destructive/10 p-3">
                                {errors.map((error, index) => (
                                    <p key={index} className="text-sm text-destructive">
                                        • {error}
                                    </p>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Questions Preview */}
                    {questions.length > 0 && (
                        <div className="space-y-2">
                            <h4 className="text-sm font-semibold">
                                Valid Questions ({questions.length})
                            </h4>
                            <ScrollArea className="h-[400px] rounded-lg border">
                                <div className="space-y-4 p-4">
                                    {questions.map((question, index) => (
                                        <div
                                            key={index}
                                            className="rounded-lg border bg-card p-4 text-card-foreground"
                                        >
                                            <div className="mb-2 flex items-start justify-between gap-2">
                                                <span className="text-xs text-muted-foreground">
                                                    Row {question.row_number}
                                                </span>
                                                <div className="flex gap-2">
                                                    <Badge variant="outline">
                                                        {typeLabels[question.type]}
                                                    </Badge>
                                                    <Badge variant="secondary">
                                                        {question.points} {question.points === 1 ? 'pt' : 'pts'}
                                                    </Badge>
                                                    {question.topic && (
                                                        <Badge variant={question.topic.exists ? 'default' : 'outline'}>
                                                            {question.topic.name}
                                                            {question.topic.will_create && ' (new)'}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                            <p className="mb-3 font-medium">
                                                {question.question_text}
                                            </p>
                                            <div className="space-y-1">
                                                {question.choices.map((choice, cIndex) => (
                                                    <div
                                                        key={cIndex}
                                                        className="flex items-center gap-2 text-sm"
                                                    >
                                                        {choice.is_correct ? (
                                                            <Check className="h-4 w-4 text-green-600" />
                                                        ) : (
                                                            <X className="h-4 w-4 text-muted-foreground" />
                                                        )}
                                                        <span
                                                            className={
                                                                choice.is_correct
                                                                    ? 'font-medium text-green-600'
                                                                    : 'text-muted-foreground'
                                                            }
                                                        >
                                                            {choice.text}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                            {question.explanation && (
                                                <p className="mt-2 text-sm italic text-muted-foreground">
                                                    Explanation: {question.explanation}
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </ScrollArea>
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onCancel} disabled={isImporting}>
                        Cancel
                    </Button>
                    <Button
                        onClick={onConfirm}
                        disabled={questions.length === 0 || isImporting}
                    >
                        {isImporting ? 'Importing...' : `Import ${questions.length} Questions`}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

