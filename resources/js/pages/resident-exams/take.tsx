import {
    ExamNavigation,
    QuestionDisplay,
    QuestionPaletteLegend,
    QuestionPaletteSidebar,
    SubmitExamDialog,
} from '@/components/resident-exams';
import { Button } from '@/components/ui/button';
import { useSidebar } from '@/components/ui/sidebar';
import { useCaptureExamMetadata } from '@/hooks/use-capture-exam-metadata';
import { useExamSessionMonitor } from '@/hooks/use-exam-session-monitor';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { Menu } from 'lucide-react';
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

    // Capture exam metadata on page load
    useCaptureExamMetadata({
        examType: exam.type,
        attemptId: attempt.id,
    });

    // Monitor session for changes and idle time
    useExamSessionMonitor({
        examType: exam.type,
        attemptId: attempt.id,
        isActive: true,
    });

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
    const [isChangingAnswer, setIsChangingAnswer] = useState(false);
    const questionRefs = useRef<(HTMLButtonElement | null)[]>([]);
    const saveTimeoutRef = useRef<NodeJS.Timeout | null>(null);
    const lastSaveTimeRef = useRef<number>(0);

    const currentQuestion = exam.questions[currentQuestionIndex];

    // Collapse the main app sidebar when entering exam mode
    useEffect(() => {
        setOpen(false);
        return () => {
            // Restore sidebar when leaving exam
            setOpen(true);
        };
    }, [setOpen]);

    // Cleanup save timeout on unmount
    useEffect(() => {
        return () => {
            if (saveTimeoutRef.current) {
                clearTimeout(saveTimeoutRef.current);
            }
        };
    }, []);

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
        let autoSubmitted = false;

        const interval = setInterval(() => {
            const now = Date.now();
            const remaining = Math.max(0, endTime - now);
            setTimeRemaining(Math.floor(remaining / 1000));

            // Auto-submit when time runs out (only once)
            if (remaining === 0 && !autoSubmitted) {
                autoSubmitted = true;
                clearInterval(interval);

                toast.warning(
                    '⏱️ Time is up! Exam is being submitted automatically...',
                    {
                        duration: 5000,
                    },
                );

                // Auto-submit without confirmation (time expired)
                setTimeout(() => {
                    router.post(
                        `/exams/${exam.type}/${attempt.id}/submit`,
                        {},
                        {
                            onSuccess: () => {
                                toast.success('Exam submitted successfully!');
                            },
                            onError: (errors) => {
                                console.error('Auto-submission error:', errors);
                                toast.error('Failed to auto-submit exam.');
                            },
                        },
                    );
                }, 1000); // Small delay to show the warning message
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [exam.duration_minutes, attempt.started_at, exam.type, attempt.id]);

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
        // Rate limiting: Prevent rapid successive changes (only runs on user interaction, not during render)
        const timeSinceLastSave = Date.now() - lastSaveTimeRef.current;

        // Minimum 300ms between answer changes to prevent gaming
        if (timeSinceLastSave < 300 && lastSaveTimeRef.current > 0) {
            toast.error('Please wait before changing your answer again');
            return;
        }

        // Update local state immediately
        setAnswers((prev) => ({
            ...prev,
            [questionId]: answerId,
        }));

        // Set loading state to disable inputs
        setIsChangingAnswer(true);
        setSaving(questionId);

        // Clear any pending save timeout
        if (saveTimeoutRef.current) {
            clearTimeout(saveTimeoutRef.current);
        }

        // Debounce the save operation (wait 500ms after last change)
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
                console.log('🔵 Saving answer to database', {
                    questionId,
                    answerData,
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
                lastSaveTimeRef.current = Date.now();
                setSaving(null);
                setIsChangingAnswer(false);
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
                setIsChangingAnswer(false);
            }
        }, 500);
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

    const answeredCount = Object.keys(answers).filter((k) =>
        isQuestionAnswered(parseInt(k)),
    ).length;
    const notAnsweredCount = exam.questions.filter(
        (q) => !isQuestionAnswered(q.id),
    ).length;

    return (
        <>
            <Head title={`Taking: ${exam.title}`} />

            <div className="relative flex h-[calc(100vh-4rem)] overflow-hidden">
                {/* Question Palette Sidebar */}
                <QuestionPaletteSidebar
                    isOpen={showSidebar}
                    onClose={() => setShowSidebar(false)}
                    questions={exam.questions}
                    currentQuestionIndex={currentQuestionIndex}
                    markedForReview={markedForReview}
                    onQuestionClick={goToQuestion}
                    isQuestionAnswered={isQuestionAnswered}
                    questionRefs={questionRefs}
                />

                {/* Main Content */}
                <div className="flex flex-1 flex-col overflow-hidden">
                    {/* Question Palette Legend */}
                    <QuestionPaletteLegend
                        answeredCount={answeredCount}
                        notAnsweredCount={notAnsweredCount}
                        markedCount={markedForReview.size}
                        timeRemaining={timeRemaining}
                        durationMinutes={exam.duration_minutes}
                        formatTime={formatTime}
                        onSubmit={handleSubmit}
                    />

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
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <div className="h-2 w-2 animate-pulse rounded-full bg-blue-500" />
                                    <span>Saving...</span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Question Content */}
                    <QuestionDisplay
                        question={currentQuestion}
                        questionIndex={currentQuestionIndex}
                        answer={answers[currentQuestion.id]}
                        isChangingAnswer={isChangingAnswer}
                        isMarked={markedForReview.has(currentQuestion.id)}
                        onAnswerChange={handleAnswerChange}
                        onToggleMark={toggleMarkForReview}
                    />

                    {/* Navigation Footer */}
                    <ExamNavigation
                        currentIndex={currentQuestionIndex}
                        totalQuestions={exam.questions.length}
                        answeredCount={answeredCount}
                        onPrevious={goToPreviousQuestion}
                        onNext={goToNextQuestion}
                    />
                </div>
            </div>

            {/* Submit Confirmation Dialog */}
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
