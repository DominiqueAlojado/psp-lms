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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { Clock } from 'lucide-react';
import { useEffect, useState } from 'react';
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

export default function TakeExam({ exam, attempt, savedAnswers }: PageProps) {
    const [timeRemaining, setTimeRemaining] = useState<number | null>(null);
    const [answers, setAnswers] = useState<Record<number, number | number[]>>(
        {},
    );
    const [saving, setSaving] = useState<number | null>(null);
    const [showSubmitDialog, setShowSubmitDialog] = useState(false);
    const [unansweredCount, setUnansweredCount] = useState(0);

    // Load saved answers on mount
    useEffect(() => {
        const loadedAnswers: Record<number, number | number[]> = {};

        Object.entries(savedAnswers).forEach(([questionId, answerData]) => {
            const qId = parseInt(questionId);
            if (answerData.choice_ids) {
                // Multiple select
                loadedAnswers[qId] = answerData.choice_ids;
            } else if (answerData.choice_id) {
                // Single choice
                loadedAnswers[qId] = answerData.choice_id;
            }
        });

        setAnswers(loadedAnswers);
    }, [savedAnswers]);

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
        setShowSubmitDialog(true);
    };

    const confirmSubmit = () => {
        // Submit to finalize (answers already saved in database)
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Taking: ${exam.title}`} />

            <div className="mx-auto max-w-4xl space-y-6 p-6">
                {/* Header */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-start justify-between">
                            <div>
                                <h1 className="text-2xl font-bold">
                                    {exam.title}
                                </h1>
                                {exam.description && (
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {exam.description}
                                    </p>
                                )}
                            </div>
                            {exam.duration_minutes &&
                                timeRemaining !== null && (
                                    <div className="flex items-center gap-2 rounded-lg border bg-muted/50 px-4 py-2">
                                        <Clock className="h-4 w-4" />
                                        <span
                                            className={
                                                timeRemaining < 300
                                                    ? 'font-mono text-lg font-bold text-destructive'
                                                    : 'font-mono text-lg font-bold'
                                            }
                                        >
                                            {formatTime(timeRemaining)}
                                        </span>
                                    </div>
                                )}
                        </div>
                        <div className="mt-4 flex gap-4 text-sm text-muted-foreground">
                            <span>{exam.questions.length} questions</span>
                            <span>{exam.total_points} points</span>
                            <span>Pass: {exam.passing_score}%</span>
                        </div>
                    </CardContent>
                </Card>

                {/* Questions */}
                <div className="space-y-6">
                    {exam.questions.map((question, index) => (
                        <Card key={question.id}>
                            <CardContent className="p-6">
                                <div className="space-y-4">
                                    <div className="flex items-start gap-4">
                                        <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-medium text-primary-foreground">
                                            {index + 1}
                                        </span>
                                        <div className="flex-1">
                                            <div
                                                className="prose prose-sm dark:prose-invert"
                                                dangerouslySetInnerHTML={{
                                                    __html: question.question_text,
                                                }}
                                            />
                                            {question.image_url && (
                                                <img
                                                    src={question.image_url}
                                                    alt="Question"
                                                    className="mt-4 max-w-md rounded-lg"
                                                />
                                            )}
                                        </div>
                                        <span className="text-sm text-muted-foreground">
                                            {question.points} pts
                                        </span>
                                    </div>

                                    <div className="ml-12 space-y-2">
                                        {question.choices.map((choice) => (
                                            <label
                                                key={choice.id}
                                                className="flex cursor-pointer items-start gap-3 rounded-lg border p-4 hover:bg-muted/50"
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
                                                    checked={
                                                        question.question_type ===
                                                        'multiple_select'
                                                            ? Array.isArray(
                                                                  answers[
                                                                      question
                                                                          .id
                                                                  ],
                                                              ) &&
                                                              (
                                                                  answers[
                                                                      question
                                                                          .id
                                                                  ] as number[]
                                                              ).includes(
                                                                  choice.id,
                                                              )
                                                            : answers[
                                                                  question.id
                                                              ] === choice.id
                                                    }
                                                    onChange={(e) => {
                                                        if (
                                                            question.question_type ===
                                                            'multiple_select'
                                                        ) {
                                                            const current =
                                                                (answers[
                                                                    question.id
                                                                ] ||
                                                                    []) as number[];
                                                            const newAnswers = e
                                                                .target.checked
                                                                ? [
                                                                      ...current,
                                                                      choice.id,
                                                                  ]
                                                                : current.filter(
                                                                      (id) =>
                                                                          id !==
                                                                          choice.id,
                                                                  );
                                                            handleAnswerChange(
                                                                question.id,
                                                                newAnswers,
                                                                question.question_type,
                                                            );
                                                        } else {
                                                            handleAnswerChange(
                                                                question.id,
                                                                choice.id,
                                                                question.question_type,
                                                            );
                                                        }
                                                    }}
                                                    className="mt-1"
                                                />
                                                <span className="flex-1">
                                                    {choice.choice_text}
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Submit */}
                <Card className="sticky bottom-6">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <div className="text-sm text-muted-foreground">
                                    {
                                        Object.keys(answers).filter((k) => {
                                            const answer = answers[parseInt(k)];
                                            return (
                                                answer !== undefined &&
                                                (Array.isArray(answer)
                                                    ? answer.length > 0
                                                    : true)
                                            );
                                        }).length
                                    }{' '}
                                    / {exam.questions.length} questions answered
                                </div>
                                {saving !== null && (
                                    <div className="text-xs text-muted-foreground">
                                        Saving...
                                    </div>
                                )}
                            </div>
                            <Button onClick={handleSubmit} size="lg">
                                Submit Exam
                            </Button>
                        </div>
                    </CardContent>
                </Card>
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
                        <AlertDialogDescription>
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
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={confirmSubmit}>
                            Submit Exam
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
