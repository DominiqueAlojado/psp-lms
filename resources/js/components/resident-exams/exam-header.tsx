import { Button } from '@/components/ui/button';
import { Menu } from 'lucide-react';
import { memo } from 'react';

interface ExamHeaderProps {
    title: string;
    currentQuestionIndex: number;
    totalQuestions: number;
    onOpenSidebar: () => void;
}

export const ExamHeader = memo(function ExamHeader({
    title,
    currentQuestionIndex,
    totalQuestions,
    onOpenSidebar,
}: ExamHeaderProps) {
    return (
        <div className="border-b bg-background p-4">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="lg:hidden"
                        onClick={onOpenSidebar}
                    >
                        <Menu className="h-5 w-5" />
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold">{title}</h1>
                        <p className="text-sm text-muted-foreground">
                            Question {currentQuestionIndex + 1} of{' '}
                            {totalQuestions}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
});
