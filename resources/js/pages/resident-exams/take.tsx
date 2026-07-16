import {
    ExamHeader,
    ExamNavigation,
    QuestionDisplay,
    QuestionPaletteLegend,
    QuestionPaletteSidebar,
    SubmitExamDialog,
} from '@/components/resident-exams';
import { useSidebar } from '@/components/ui/sidebar';
import { useCaptureExamMetadata } from '@/hooks/use-capture-exam-metadata';
import { useExamSessionMonitor } from '@/hooks/use-exam-session-monitor';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
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

    useCaptureExamMetadata({
        examType: exam.type,
        attemptId: attempt.id,
    });

    useExamSessionMonitor({
        examType: exam.type,
        attemptId: attempt.id,
        isActive: true,
    });

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
    const saveTimeoutRef = useRef<NodeJS.Timeout | null>(null);
    const lastSaveTimeRef = useRef<number>(0);
    const hasAutoSubmittedRef = useRef(false);

    const currentQuestion = exam.questions[currentQuestionIndex];

    useEffect(() => {
        setOpen(false);
        return () => {
            setOpen(true);
        };
    }, [setOpen]);

    useEffect(() => {
        return () => {
            if (saveTimeoutRef.current) {
                clearTimeout(saveTimeoutRef.current);
            }
        };
    }, []);

    useEffect(() => {
        const currentRef = questionRefs.current[currentQuestionIndex];
        if (currentRef) {
            currentRef.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
            });
        }
    }, [currentQuestionIndex]);

    const handleAnswerChange = (
        questionId: number,
        answerId: number | number[],
        questionType: string,
    ) => {
        const timeSinceLastSave = Date.now() - lastSaveTimeRef.current;

        if (timeSinceLastSave < 300 && lastSaveTimeRef.current > 0) {
            toast.error('Please wait before changing your answer again');
            return;
        }

        setAnswers((prev) => ({
            ...prev,
            [questionId]: answerId,
        }));

        setSaving(questionId);

        if (saveTimeoutRef.current) {
            clearTimeout(saveTimeoutRef.current);
        }

        saveTimeoutRef.current = setTimeout(async () => {
            const answerData =
                questionType === 'multiple_select'
                    ? {
                          choice_ids: Array.isArray(answerId)
                              ? answerId
                              : [answerId],
                      }
                    : { choice_id: answerId };

            try {
                await axios.post(`/exams/${exam.type}/${attempt.id}/save-answer`, {
                    question_id: questionId,
                    answer_data: answerData,
                });

                lastSaveTimeRef.current = Date.now();
                setSaving(null);
            } catch (error) {
                const axiosError = error as {
                    response?: { data?: { message?: string } };
                };
                toast.error(
                    axiosError.response?.data?.message ||
                        'Failed to save answer. Please try again.',
                );
                setSaving(null);
            }
        }, 500);
    };

    const handleSubmit = useCallback(() => {
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
    }, [answers, exam.questions.length]);

    const confirmSubmit = useCallback(() => {
        if (confirmText.toUpperCase() !== 'FINALIZE') {
            toast.error('Please type FINALIZE to confirm submission');
            return;
        }

        setShowSubmitDialog(false);
        router.post(
            `/exams/${exam.type}/${attempt.id}/submit`,
            {},
            {
                onSuccess: () => {
                    toast.success('Exam submitted successfully!');
                },
                onError: () => {
                    toast.error('Failed to submit exam. Please try again.');
                },
            },
        );
    }, [attempt.id, confirmText, exam.type]);

    const handleTimeExpired = useCallback(() => {
        if (hasAutoSubmittedRef.current) {
            return;
        }

        hasAutoSubmittedRef.current = true;

        toast.warning('Time is up! Exam is being submitted automatically...', {
            duration: 5000,
        });

        setTimeout(() => {
            router.post(
                `/exams/${exam.type}/${attempt.id}/submit`,
                {},
                {
                    onSuccess: () => {
                        toast.success('Exam submitted successfully!');
                    },
                    onError: () => {
                        toast.error('Failed to auto-submit exam.');
                    },
                },
            );
        }, 1000);
    }, [attempt.id, exam.type]);

    const goToQuestion = useCallback(
        (index: number) => {
            if (index >= 0 && index < exam.questions.length) {
                setCurrentQuestionIndex(index);
            }
        },
        [exam.questions.length],
    );

    const goToNextQuestion = useCallback(() => {
        if (currentQuestionIndex < exam.questions.length - 1) {
            setCurrentQuestionIndex(currentQuestionIndex + 1);
        }
    }, [currentQuestionIndex, exam.questions.length]);

    const goToPreviousQuestion = useCallback(() => {
        if (currentQuestionIndex > 0) {
            setCurrentQuestionIndex(currentQuestionIndex - 1);
        }
    }, [currentQuestionIndex]);

    const toggleMarkForReview = useCallback((questionId: number) => {
        setMarkedForReview((prev) => {
            const next = new Set(prev);
            if (next.has(questionId)) {
                next.delete(questionId);
            } else {
                next.add(questionId);
            }
            return next;
        });
    }, []);

    const answeredQuestionIds = useMemo(() => {
        const answeredIds = new Set<number>();

        Object.entries(answers).forEach(([questionId, answer]) => {
            if (
                answer !== undefined &&
                (Array.isArray(answer) ? answer.length > 0 : true)
            ) {
                answeredIds.add(Number(questionId));
            }
        });

        return answeredIds;
    }, [answers]);

    const answeredCount = answeredQuestionIds.size;
    const notAnsweredCount = exam.questions.length - answeredCount;

    const handleOpenSidebar = useCallback(() => {
        setShowSidebar(true);
    }, []);

    const handleCloseSidebar = useCallback(() => {
        setShowSidebar(false);
    }, []);

    return (
        <>
            <Head title={`Taking: ${exam.title}`} />

            <div className="relative flex h-[calc(100vh-4rem)] overflow-hidden">
                <QuestionPaletteSidebar
                    isOpen={showSidebar}
                    onClose={handleCloseSidebar}
                    questions={exam.questions}
                    currentQuestionIndex={currentQuestionIndex}
                    markedForReview={markedForReview}
                    answeredQuestionIds={answeredQuestionIds}
                    onQuestionClick={goToQuestion}
                    questionRefs={questionRefs}
                />

                <div className="flex flex-1 flex-col overflow-hidden">
                    <QuestionPaletteLegend
                        answeredCount={answeredCount}
                        notAnsweredCount={notAnsweredCount}
                        markedCount={markedForReview.size}
                        durationMinutes={exam.duration_minutes}
                        startedAt={attempt.started_at}
                        onSubmit={handleSubmit}
                        onTimeExpired={handleTimeExpired}
                    />

                    <ExamHeader
                        title={exam.title}
                        currentQuestionIndex={currentQuestionIndex}
                        totalQuestions={exam.questions.length}
                        onOpenSidebar={handleOpenSidebar}
                    />

                    <QuestionDisplay
                        question={currentQuestion}
                        questionIndex={currentQuestionIndex}
                        answer={answers[currentQuestion.id]}
                        isChangingAnswer={saving === currentQuestion.id}
                        isMarked={markedForReview.has(currentQuestion.id)}
                        onAnswerChange={handleAnswerChange}
                        onToggleMark={toggleMarkForReview}
                    />

                    <ExamNavigation
                        currentIndex={currentQuestionIndex}
                        totalQuestions={exam.questions.length}
                        answeredCount={answeredCount}
                        onPrevious={goToPreviousQuestion}
                        onNext={goToNextQuestion}
                    />
                </div>
            </div>

            <SubmitExamDialog
                open={showSubmitDialog}
                onOpenChange={setShowSubmitDialog}
                unansweredCount={unansweredCount}
                confirmText={confirmText}
                onConfirmTextChange={setConfirmText}
                onConfirm={confirmSubmit}
            />
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
