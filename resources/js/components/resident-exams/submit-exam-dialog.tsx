import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Input } from '@/components/ui/input';

// Submit exam confirmation dialog component

interface SubmitExamDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    unansweredCount: number;
    confirmText: string;
    onConfirmTextChange: (text: string) => void;
    onConfirm: () => void;
}

export function SubmitExamDialog({
    open,
    onOpenChange,
    unansweredCount,
    confirmText,
    onConfirmTextChange,
    onConfirm,
}: SubmitExamDialogProps) {
    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {unansweredCount > 0
                            ? 'Submit with Unanswered Questions?'
                            : 'Submit Exam?'}
                    </AlertDialogTitle>
                    <AlertDialogDescription className="space-y-4">
                        <div>
                            {unansweredCount > 0 ? (
                                <>
                                    You have{' '}
                                    <span className="font-semibold text-destructive">
                                        {unansweredCount} unanswered question
                                        {unansweredCount > 1 ? 's' : ''}
                                    </span>
                                    . Are you sure you want to submit anyway?
                                    You cannot change your answers after
                                    submission.
                                </>
                            ) : (
                                'Are you sure you want to submit this exam? You cannot change your answers after submission.'
                            )}
                        </div>
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-foreground">
                                Type{' '}
                                <span className="font-bold text-destructive">
                                    FINALIZE
                                </span>{' '}
                                to confirm:
                            </p>
                            <Input
                                type="text"
                                value={confirmText}
                                onChange={(e) =>
                                    onConfirmTextChange(e.target.value)
                                }
                                placeholder="Type FINALIZE"
                                className="uppercase"
                                autoFocus
                            />
                        </div>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel onClick={() => onConfirmTextChange('')}>
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={onConfirm}
                        disabled={confirmText.toUpperCase() !== 'FINALIZE'}
                        className="bg-destructive hover:bg-destructive/90"
                    >
                        Submit Exam4
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
