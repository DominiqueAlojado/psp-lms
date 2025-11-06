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
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useSidebar } from '@/components/ui/sidebar';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { ChevronLeft, ChevronRight, Clock, Flag, Menu, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My Exams',
        href: '/resident-exams',
    },
    {
        title: 'Take Exam',
        href: '#',
    },
];

interface Question {
    id: number;
    question_type: 'multiple_choice' | 'multiple_select' | 'true_false';
    question_text: string;
    points: number;
    image_url: string | null;
    choices: {
        id: number;
        choice_text: string;
    }[];
}

interface Exam {
    id: number;
    type: 'institution' | 'inservice';
    title: string;
    description: string | null;
    duration_minutes: number | null;
    total_points: number;
    passing_score: number;
    randomize_questions: boolean;
    randomize_choices: boolean;
    questions: Question[];
}

interface Attempt {
    id: number;
    started_at: string;
}

interface PageProps {
    exam: Exam;
    attempt: Attempt;
    savedAnswers: Record<number, { choice_id?: number; choice_ids?: number[] }>;
    [key: string]: unknown;
}

function ExamContent({ exam, attempt, savedAnswers }: PageProps) {
    const { setOpen } = useSidebar();
    const [timeRemaining, setTimeRemaining] = useState<number | null>(null);
    const [answers, setAnswers] = useState<Record<number, number | number[]>>(
        () => {
            const loadedAnswers: Record<number, number | number[]> = {};
            Object.entries(savedAnswers).forEach(([questionId, answerData]) => {
                const qId = parseInt(questionId);
                if (answerData.choice_ids) {
                    loadedAnswers[qId] = answerData.choice_ids;
                } else if (answerData.choice_id) {
                    loadedAnswers[qId] = answerData.choice_id;
                }
            });
            return loadedAnswers;
        },
    );
    const [saving, setSaving] = useState<number | null>(null);
    const [showSubmitDialog, setShowSubmitDialog] = useState(false);
    const [unansweredCount, setUnansweredCount] = useState(0);
    const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
    const [showSidebar, setShowSidebar] = useState(true);
    const [markedForReview, setMarkedForReview] = useState<Set<number>>(
        new Set(),
    );
    const [confirmText, setConfirmText] = useState('');
    const questionRefs = useRef<(HTMLButtonElement | null)[]>([]);

    const currentQuestion = exam.questions[currentQuestionIndex];

    // Collapse the main app sidebar when entering exam mode
    useEffect(() => {
        setOpen(false);
        return () => {
            // Restore sidebar when leaving exam
            setOpen(true);
        };
    }, [setOpen]);

    // Auto-scroll to current question in sidebar
    useEffect(() => {
        const currentRef = questionRefs.current[currentQuestionIndex];
        if (currentRef) {
            currentRef.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
            });
        }
    }, [currentQuestionIndex]);

    // Calculate time remaining
    useEffect(() => {
        if (!exam.duration_minutes) return;

        const startTime = new Date(attempt.started_at).getTime();
        const endTime = startTime + exam.duration_minutes * 60 * 1000;

        const interval = setInterval(() => {
            const now = Date.now();
            const remaining = Math.max(0, endTime - now);
            setTimeRemaining(Math.floor(remaining / 1000));

            if (remaining === 0) {
                // Auto-submit when time runs out
                // TODO: Implement auto-submit
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [exam.duration_minutes, attempt.started_at]);

    const formatTime = (seconds: number) => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    const handleAnswerChange = async (
        questionId: number,
        answerId: number | number[],
        questionType: string,
    ) => {
        // Update local state immediately
        setAnswers((prev) => ({
            ...prev,
            [questionId]: answerId,
        }));

        // Auto-save to backend
        setSaving(questionId);

        const answerData =
            questionType === 'multiple_select'
                ? {
                      choice_ids: Array.isArray(answerId)
                          ? answerId
                          : [answerId],
                  }
                : { choice_id: answerId };

        try {
            console.log('🔵 Using axios.post() to save answer', {
                questionId,
                answerData,
                url: `/exams/${exam.type}/${attempt.id}/save-answer`,
            });

            // Use axios which handles CSRF automatically
            const response = await axios.post(
                `/exams/${exam.type}/${attempt.id}/save-answer`,
                {
                    question_id: questionId,
                    answer_data: answerData,
                },
            );

            console.log('✅ Answer saved successfully', response.data);
            setSaving(null);
        } catch (error) {
            console.error('❌ Failed to save answer:', error);
            const axiosError = error as {
                response?: { data?: { message?: string } };
            };
            toast.error(
                axiosError.response?.data?.message ||
                    'Failed to save answer. Please try again.',
            );
            setSaving(null);
        }
    };

    const handleSubmit = () => {
        // Check if all questions are answered
        const count =
            exam.questions.length -
            Object.keys(answers).filter((k) => {
                const answer = answers[parseInt(k)];
                return (
                    answer !== undefined &&
                    (Array.isArray(answer) ? answer.length > 0 : true)
                );
            }).length;

        setUnansweredCount(count);
        setConfirmText('');
        setShowSubmitDialog(true);
    };

    const confirmSubmit = () => {
        if (confirmText.toUpperCase() !== 'FINALIZE') {
            toast.error('Please type FINALIZE to confirm submission');
            return;
        }

        // Submit to finalize (answers already saved in database)
        setShowSubmitDialog(false);
        router.post(
            `/exams/${exam.type}/${attempt.id}/submit`,
            {},
            {
                onSuccess: () => {
                    toast.success('Exam submitted successfully!');
                },
                onError: (errors) => {
                    console.error('Submission error:', errors);
                    toast.error('Failed to submit exam. Please try again.');
                },
            },
        );
    };

    const isQuestionAnswered = (questionId: number): boolean => {
        const answer = answers[questionId];
        return (
            answer !== undefined &&
            (Array.isArray(answer) ? answer.length > 0 : true)
        );
    };

    const goToQuestion = (index: number) => {
        if (index >= 0 && index < exam.questions.length) {
            setCurrentQuestionIndex(index);
        }
    };

    const goToNextQuestion = () => {
        if (currentQuestionIndex < exam.questions.length - 1) {
            setCurrentQuestionIndex(currentQuestionIndex + 1);
        }
    };

    const goToPreviousQuestion = () => {
        if (currentQuestionIndex > 0) {
            setCurrentQuestionIndex(currentQuestionIndex - 1);
        }
    };

    const toggleMarkForReview = (questionId: number) => {
        setMarkedForReview((prev) => {
            const newSet = new Set(prev);
            if (newSet.has(questionId)) {
                newSet.delete(questionId);
            } else {
                newSet.add(questionId);
            }
            return newSet;
        });
    };

    return (
        <>
            <Head title={`Taking: ${exam.title}`} />

            <div className="relative flex h-[calc(100vh-4rem)] overflow-hidden">
                {/* Question Palette Sidebar */}
                <div
                    className={cn(
                        'fixed inset-y-0 left-0 z-40 w-80 transform border-r bg-background transition-transform duration-300 lg:relative lg:translate-x-0',
                        showSidebar ? 'translate-x-0' : '-translate-x-full',
                    )}
                >
                    <div className="flex h-full flex-col">
                        {/* Sidebar Header */}
                        <div className="border-b p-4">
                            <div className="flex items-center justify-between">
                                <h2 className="font-semibold">
                                    Question Palette
                                </h2>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="lg:hidden"
                                    onClick={() => setShowSidebar(false)}
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>
                            <div className="mt-3 space-y-2 text-sm">
                                <div className="flex items-center gap-2">
                                    <div className="h-8 w-8 rounded-lg border-2 border-green-500 bg-green-500/20" />
                                    <span className="text-xs">
                                        Answered ({' '}
                                        {
                                            Object.keys(answers).filter((k) =>
                                                isQuestionAnswered(parseInt(k)),
                                            ).length
                                        }{' '}
                                        )
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="h-8 w-8 rounded-lg border-2 border-muted-foreground" />
                                    <span className="text-xs">
                                        Not Answered ({' '}
                                        {
                                            exam.questions.filter(
                                                (q) =>
                                                    !isQuestionAnswered(q.id),
                                            ).length
                                        }{' '}
                                        )
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="h-8 w-8 rounded-lg border-2 border-primary bg-primary/20" />
                                    <span className="text-xs">Current</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-orange-500 bg-orange-500/20">
                                        <Flag className="h-4 w-4 fill-orange-500 text-orange-500" />
                                    </div>
                                    <span className="text-xs">
                                        Marked for Review ({' '}
                                        {markedForReview.size} )
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Question List */}
                        <div className="flex-1 overflow-y-auto p-4">
                            <div className="space-y-2">
                                {exam.questions.map((question, index) => {
                                    const answered = isQuestionAnswered(
                                        question.id,
                                    );
                                    const current =
                                        index === currentQuestionIndex;
                                    const marked = markedForReview.has(
                                        question.id,
                                    );

                                    return (
                                        <button
                                            key={question.id}
                                            ref={(el) => {
                                                questionRefs.current[index] =
                                                    el;
                                            }}
                                            onClick={() => goToQuestion(index)}
                                            className={cn(
                                                'flex w-full items-center gap-3 rounded-lg border-2 px-4 py-2.5 text-left transition-all',
                                                current &&
                                                    'border-primary bg-primary/20 ring-2 ring-primary ring-offset-2',
                                                answered &&
                                                    !current &&
                                                    'border-green-500 bg-green-500/20 hover:bg-green-500/30',
                                                !answered &&
                                                    !current &&
                                                    'border-muted-foreground hover:border-primary hover:bg-muted',
                                            )}
                                        >
                                            <span
                                                className={cn(
                                                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-sm font-semibold',
                                                    current &&
                                                        'bg-primary text-primary-foreground',
                                                    answered &&
                                                        !current &&
                                                        'bg-green-500 text-white',
                                                    !answered &&
                                                        !current &&
                                                        'bg-muted',
                                                )}
                                            >
                                                {index + 1}
                                            </span>
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <p className="text-sm font-medium">
                                                        Item No. {index + 1}
                                                    </p>
                                                    {marked && (
                                                        <Flag className="h-3.5 w-3.5 fill-orange-500 text-orange-500" />
                                                    )}
                                                </div>
                                                <p className="text-xs text-muted-foreground">
                                                    {answered
                                                        ? 'Answered'
                                                        : 'Not answered'}
                                                </p>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Timer and Submit */}
                        <div className="space-y-3 border-t p-4">
                            {exam.duration_minutes &&
                                timeRemaining !== null && (
                                    <div className="flex items-center justify-center gap-2 rounded-lg border bg-muted/50 px-4 py-3">
                                        <Clock className="h-4 w-4" />
                                        <span
                                            className={cn(
                                                'font-mono text-lg font-bold',
                                                timeRemaining < 300 &&
                                                    'text-destructive',
                                            )}
                                        >
                                            {formatTime(timeRemaining)}
                                        </span>
                                    </div>
                                )}
                            <Button
                                onClick={handleSubmit}
                                className="w-full"
                                size="lg"
                            >
                                Submit Exam
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Main Content */}
                <div className="flex flex-1 flex-col overflow-hidden">
                    {/* Header */}
                    <div className="border-b bg-background p-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="lg:hidden"
                                    onClick={() => setShowSidebar(true)}
                                >
                                    <Menu className="h-5 w-5" />
                                </Button>
                                <div>
                                    <h1 className="text-xl font-bold">
                                        {exam.title}
                                    </h1>
                                    <p className="text-sm text-muted-foreground">
                                        Question {currentQuestionIndex + 1} of{' '}
                                        {exam.questions.length}
                                    </p>
                                </div>
                            </div>
                            {saving !== null && (
                                <div className="text-sm text-muted-foreground">
                                    Saving...
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Question Content */}
                    <div className="flex-1 overflow-y-auto p-6">
                        <div className="mx-auto max-w-4xl">
                            <Card>
                                <CardContent className="p-8">
                                    <div className="space-y-6">
                                        {/* Question Header */}
                                        <div className="flex items-start gap-4">
                                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-base font-medium text-primary-foreground">
                                                {currentQuestionIndex + 1}
                                            </span>
                                            <div className="flex-1">
                                                <div
                                                    className="prose prose-base dark:prose-invert max-w-none"
                                                    dangerouslySetInnerHTML={{
                                                        __html: currentQuestion.question_text,
                                                    }}
                                                />
                                                {currentQuestion.image_url && (
                                                    <img
                                                        src={
                                                            currentQuestion.image_url
                                                        }
                                                        alt="Question"
                                                        className="mt-4 max-w-md rounded-lg border"
                                                    />
                                                )}
                                            </div>
                                            <div className="flex flex-col items-end gap-2">
                                                <span className="text-sm font-medium text-muted-foreground">
                                                    {currentQuestion.points}{' '}
                                                    {currentQuestion.points ===
                                                    1
                                                        ? 'point'
                                                        : 'points'}
                                                </span>
                                                <Button
                                                    variant={
                                                        markedForReview.has(
                                                            currentQuestion.id,
                                                        )
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                    size="sm"
                                                    onClick={() =>
                                                        toggleMarkForReview(
                                                            currentQuestion.id,
                                                        )
                                                    }
                                                    className={cn(
                                                        markedForReview.has(
                                                            currentQuestion.id,
                                                        ) &&
                                                            'bg-orange-500 hover:bg-orange-600',
                                                    )}
                                                >
                                                    <Flag
                                                        className={cn(
                                                            'h-4 w-4',
                                                            markedForReview.has(
                                                                currentQuestion.id,
                                                            ) && 'fill-current',
                                                        )}
                                                    />
                                                    <span className="ml-2">
                                                        {markedForReview.has(
                                                            currentQuestion.id,
                                                        )
                                                            ? 'Marked'
                                                            : 'Mark'}
                                                    </span>
                                                </Button>
                                            </div>
                                        </div>

                                        {/* Choices */}
                                        <div className="space-y-3 pl-14">
                                            {currentQuestion.choices.map(
                                                (choice) => (
                                                    <label
                                                        key={choice.id}
                                                        className={cn(
                                                            'flex cursor-pointer items-start gap-3 rounded-lg border-2 p-4 transition-all hover:bg-muted/50',
                                                            (currentQuestion.question_type ===
                                                            'multiple_select'
                                                                ? Array.isArray(
                                                                      answers[
                                                                          currentQuestion
                                                                              .id
                                                                      ],
                                                                  ) &&
                                                                  (
                                                                      answers[
                                                                          currentQuestion
                                                                              .id
                                                                      ] as number[]
                                                                  ).includes(
                                                                      choice.id,
                                                                  )
                                                                : answers[
                                                                      currentQuestion
                                                                          .id
                                                                  ] ===
                                                                  choice.id) &&
                                                                'border-primary bg-primary/5',
                                                        )}
                                                    >
                                                        <input
                                                            type={
                                                                currentQuestion.question_type ===
                                                                'multiple_select'
                                                                    ? 'checkbox'
                                                                    : 'radio'
                                                            }
                                                            name={`question_${currentQuestion.id}`}
                                                            value={choice.id}
                                                            checked={
                                                                currentQuestion.question_type ===
                                                                'multiple_select'
                                                                    ? Array.isArray(
                                                                          answers[
                                                                              currentQuestion
                                                                                  .id
                                                                          ],
                                                                      ) &&
                                                                      (
                                                                          answers[
                                                                              currentQuestion
                                                                                  .id
                                                                          ] as number[]
                                                                      ).includes(
                                                                          choice.id,
                                                                      )
                                                                    : answers[
                                                                          currentQuestion
                                                                              .id
                                                                      ] ===
                                                                      choice.id
                                                            }
                                                            onChange={(e) => {
                                                                if (
                                                                    currentQuestion.question_type ===
                                                                    'multiple_select'
                                                                ) {
                                                                    const current =
                                                                        (answers[
                                                                            currentQuestion
                                                                                .id
                                                                        ] ||
                                                                            []) as number[];
                                                                    const newAnswers =
                                                                        e.target
                                                                            .checked
                                                                            ? [
                                                                                  ...current,
                                                                                  choice.id,
                                                                              ]
                                                                            : current.filter(
                                                                                  (
                                                                                      id,
                                                                                  ) =>
                                                                                      id !==
                                                                                      choice.id,
                                                                              );
                                                                    handleAnswerChange(
                                                                        currentQuestion.id,
                                                                        newAnswers,
                                                                        currentQuestion.question_type,
                                                                    );
                                                                } else {
                                                                    handleAnswerChange(
                                                                        currentQuestion.id,
                                                                        choice.id,
                                                                        currentQuestion.question_type,
                                                                    );
                                                                }
                                                            }}
                                                            className="mt-0.5"
                                                        />
                                                        <span className="flex-1 text-base">
                                                            {choice.choice_text}
                                                        </span>
                                                    </label>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    {/* Navigation Footer */}
                    <div className="border-t bg-background p-4">
                        <div className="mx-auto flex max-w-4xl items-center justify-between">
                            <Button
                                variant="outline"
                                onClick={goToPreviousQuestion}
                                disabled={currentQuestionIndex === 0}
                            >
                                <ChevronLeft className="mr-2 h-4 w-4" />
                                Previous
                            </Button>
                            <div className="text-sm text-muted-foreground">
                                {
                                    Object.keys(answers).filter((k) =>
                                        isQuestionAnswered(parseInt(k)),
                                    ).length
                                }{' '}
                                / {exam.questions.length} answered
                            </div>
                            <Button
                                onClick={goToNextQuestion}
                                disabled={
                                    currentQuestionIndex ===
                                    exam.questions.length - 1
                                }
                            >
                                Next
                                <ChevronRight className="ml-2 h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Submit Confirmation Dialog */}
            <AlertDialog
                open={showSubmitDialog}
                onOpenChange={setShowSubmitDialog}
            >
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
                                            {unansweredCount} unanswered
                                            question
                                            {unansweredCount > 1 ? 's' : ''}
                                        </span>
                                        . Are you sure you want to submit
                                        anyway? You cannot change your answers
                                        after submission.
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
                                        setConfirmText(e.target.value)
                                    }
                                    placeholder="Type FINALIZE"
                                    className="uppercase"
                                    autoFocus
                                />
                            </div>
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => setConfirmText('')}>
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmSubmit}
                            disabled={confirmText.toUpperCase() !== 'FINALIZE'}
                            className="bg-destructive hover:bg-destructive/90"
                        >
                            Submit Exam
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}

export default function TakeExam(props: PageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <ExamContent {...props} />
        </AppLayout>
    );
}
