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
import { Trash2 } from 'lucide-react';

interface Props {
    open: boolean;
    title?: string;
    description?: string;
    itemName?: string;
    itemIdentifier?: string;
    warningMessage?: string;
    confirmText?: string;
    cancelText?: string;
    onConfirm: () => void;
    onCancel: () => void;
}

export function DeleteConfirmationDialog({
    open,
    title = 'Delete Item?',
    description,
    itemName,
    itemIdentifier,
    warningMessage = 'This action cannot be undone. This will permanently delete this item.',
    confirmText = 'Delete',
    cancelText = 'Cancel',
    onConfirm,
    onCancel,
}: Props) {
    const truncateText = (text: string, maxLength: number = 100) => {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    };

    return (
        <AlertDialog open={open} onOpenChange={(open) => !open && onCancel()}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {itemIdentifier && (
                            <span className="font-medium">
                                {itemIdentifier}
                                {itemName && ': '}
                            </span>
                        )}
                        {itemName && (
                            <span className="mt-1 block text-foreground/70">
                                "{truncateText(itemName)}"
                            </span>
                        )}
                        {description && (
                            <span className="mt-2 block">{description}</span>
                        )}
                        <span className="mt-3 block">{warningMessage}</span>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel onClick={onCancel}>
                        {cancelText}
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={onConfirm}
                        className="bg-destructive text-white hover:bg-destructive/90"
                    >
                        <Trash2 className="mr-2 h-4 w-4" />
                        {confirmText}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

