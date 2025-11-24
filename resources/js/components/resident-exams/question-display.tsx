import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Flag } from 'lucide-react';
import { useState } from 'react';

interface Choice {
    id: number;
    choice_text: string;
}

interface Question {
    id: number;
    question_type: 'multiple_choice' | 'multiple_select' | 'true_false';
    question_text: string;
    points: number;
    image_url: string | null;
    choices: Choice[];
}

interface QuestionDisplayProps {
    question: Question;
    questionIndex: number;
    answer: number | number[] | undefined;
    isChangingAnswer: boolean;
    isMarked: boolean;
    onAnswerChange: (
        questionId: number,
        answerId: number | number[],
        questionType: string,
    ) => void;
    onToggleMark: (questionId: number) => void;
}

const MAGNIFIER_SIZE = 180;

export function QuestionDisplay({
    question,
    questionIndex,
    answer,
    isChangingAnswer,
    isMarked,
    onAnswerChange,
    onToggleMark,
}: QuestionDisplayProps) {
    const [isMagnifierVisible, setIsMagnifierVisible] = useState(false);
    const [magnifierConfig, setMagnifierConfig] = useState({
        backgroundPosition: '0% 0%',
        backgroundSize: 'contain',
        top: 0,
        left: 0,
    });

    const handleMagnifierMove = (
        event: React.MouseEvent<HTMLImageElement, MouseEvent>,
    ) => {
        const img = event.currentTarget;
        const { offsetX, offsetY } = event.nativeEvent;

        const xPercent = (offsetX / img.width) * 100;
        const yPercent = (offsetY / img.height) * 100;

        setMagnifierConfig({
            backgroundPosition: `${xPercent}% ${yPercent}%`,
            backgroundSize: `${img.naturalWidth}px ${img.naturalHeight}px`,
            top: offsetY - MAGNIFIER_SIZE / 2,
            left: offsetX - MAGNIFIER_SIZE / 2,
        });
    };

    return (
        <div className="flex-1 overflow-y-auto p-6">
            <div className="mx-auto max-w-4xl">
                {/* Mark for Review Button - Visible on small screens only */}
                <div className="mb-4 flex justify-end">
                    <Button
                        variant={isMarked ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => onToggleMark(question.id)}
                        className={cn(
                            isMarked && 'bg-orange-500 hover:bg-orange-600',
                        )}
                    >
                        <Flag
                            className={cn(
                                'h-4 w-4',
                                isMarked && 'fill-current',
                            )}
                        />
                        <span className="ml-2">
                            {isMarked ? 'Marked' : 'Mark'}
                        </span>
                    </Button>
                </div>
                <div className="space-y-6">
                    {/* Question Header */}
                    <div className="flex items-start gap-4">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-base font-medium text-primary-foreground">
                            {questionIndex + 1}
                        </span>
                        <div className="flex-1">
                            <div
                                className="prose prose-base dark:prose-invert max-w-none"
                                dangerouslySetInnerHTML={{
                                    __html: question.question_text,
                                }}
                            />
                            {question.image_url && (
                                <div className="relative mt-4 inline-block bg-muted/10 p-3">
                                    <img
                                        src={question.image_url}
                                        alt="Question"
                                        className="max-w-md rounded-md border bg-white"
                                        onMouseEnter={() =>
                                            setIsMagnifierVisible(true)
                                        }
                                        onMouseLeave={() =>
                                            setIsMagnifierVisible(false)
                                        }
                                        onMouseMove={handleMagnifierMove}
                                    />
                                    {isMagnifierVisible && (
                                        <div
                                            className="pointer-events-none absolute hidden rounded-full border-2 border-primary shadow-lg md:block"
                                            style={{
                                                width: MAGNIFIER_SIZE,
                                                height: MAGNIFIER_SIZE,
                                                top: magnifierConfig.top,
                                                left: magnifierConfig.left,
                                                backgroundImage: `url(${question.image_url})`,
                                                backgroundRepeat: 'no-repeat',
                                                backgroundPosition:
                                                    magnifierConfig.backgroundPosition,
                                                backgroundSize:
                                                    magnifierConfig.backgroundSize,
                                            }}
                                        />
                                    )}
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Hover to magnify image
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Choices */}
                    <div className="space-y-3 pl-14">
                        {question.choices.map((choice) => (
                            <label
                                key={choice.id}
                                className={cn(
                                    'flex cursor-pointer items-start gap-3 rounded-lg border-2 p-4 transition-all',
                                    !isChangingAnswer && 'hover:bg-muted/50',
                                    isChangingAnswer &&
                                        'cursor-not-allowed opacity-60',
                                    (question.question_type ===
                                    'multiple_select'
                                        ? Array.isArray(answer) &&
                                          answer.includes(choice.id)
                                        : answer === choice.id) &&
                                        'border-primary bg-primary/5',
                                )}
                            >
                                <input
                                    type={
                                        question.question_type ===
                                        'multiple_select'
                                            ? 'checkbox'
                                            : 'radio'
                                    }
                                    name={`question_${question.id}`}
                                    value={choice.id}
                                    disabled={isChangingAnswer}
                                    checked={
                                        question.question_type ===
                                        'multiple_select'
                                            ? Array.isArray(answer) &&
                                              answer.includes(choice.id)
                                            : answer === choice.id
                                    }
                                    onChange={(e) => {
                                        if (
                                            question.question_type ===
                                            'multiple_select'
                                        ) {
                                            const current = (answer ||
                                                []) as number[];
                                            const newAnswers = e.target.checked
                                                ? [...current, choice.id]
                                                : current.filter(
                                                      (id) => id !== choice.id,
                                                  );
                                            onAnswerChange(
                                                question.id,
                                                newAnswers,
                                                question.question_type,
                                            );
                                        } else {
                                            onAnswerChange(
                                                question.id,
                                                choice.id,
                                                question.question_type,
                                            );
                                        }
                                    }}
                                    className="mt-0.5"
                                />
                                <span className="flex-1 text-base">
                                    {choice.choice_text}
                                </span>
                            </label>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
