import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface ExamNavigationProps {
    currentIndex: number;
    totalQuestions: number;
    answeredCount: number;
    onPrevious: () => void;
    onNext: () => void;
}

export function ExamNavigation({
    currentIndex,
    totalQuestions,
    answeredCount,
    onPrevious,
    onNext,
}: ExamNavigationProps) {
    return (
        <div className="border-t bg-background p-4">
            <div className="mx-auto flex max-w-4xl items-center justify-between">
                <Button
                    variant="outline"
                    onClick={onPrevious}
                    disabled={currentIndex === 0}
                >
                    <ChevronLeft className="mr-2 h-4 w-4" />
                    Previous
                </Button>
                <div className="text-sm text-muted-foreground">
                    {answeredCount} / {totalQuestions} answered
                </div>
                <Button
                    onClick={onNext}
                    disabled={currentIndex === totalQuestions - 1}
                >
                    Next
                    <ChevronRight className="ml-2 h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}
