import { ExamResultsSheet } from '@/components/exam-results-dialog';
import HeadingSmall from '@/components/heading-small';
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
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { preserveOrgParam } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, Clock, FileText, Play } from 'lucide-react';
import { useState } from 'react';

interface Exam {
    id: number;
    title: string;
    description: string | null;
    exam_category: string | null;
    type: 'institution' | 'inservice';
    questions_count: number;
    total_points: number;
    passing_score: number;
    duration_minutes: number | null;
    is_available: boolean;
    available_from: string | null;
    available_until: string | null;
    attempt_count: number;
    max_attempts: number | null;
    best_score: number | null;
    last_attempted: string | null;
    has_in_progress_attempt: boolean;
}

interface PageProps {
    availableExams: Exam[];
    completedExams: Exam[];
    upcomingExams: Exam[];
    [key: string]: unknown;
}

export default function ResidentExams() {
    const { availableExams, completedExams, upcomingExams, auth } =
        usePage<PageProps & SharedData>().props;
    const currentOrgSlug = auth.currentOrganization?.slug;

    const [showStartDialog, setShowStartDialog] = useState(false);
    const [showResultsDialog, setShowResultsDialog] = useState(false);
    const [selectedExam, setSelectedExam] = useState<Exam | null>(null);
    const [confirmText, setConfirmText] = useState('');
    const [actionType, setActionType] = useState<'start' | 'resume' | 'retake'>(
        'start',
    );

    const handleExamAction = (
        exam: Exam,
        action: 'start' | 'resume' | 'retake',
    ) => {
        setSelectedExam(exam);
        setActionType(action);
        setConfirmText('');
        setShowStartDialog(true);
    };

    const handleViewResults = (exam: Exam) => {
        setSelectedExam(exam);
        setShowResultsDialog(true);
    };

    const handleRetakeFromResults = () => {
        if (selectedExam) {
            setShowResultsDialog(false);
            handleExamAction(selectedExam, 'retake');
        }
    };

    const confirmStartExam = () => {
        if (
            actionType !== 'resume' &&
            confirmText.toUpperCase() !== 'START EXAM'
        ) {
            return;
        }

        if (selectedExam) {
            router.visit(
                preserveOrgParam(
                    `/exams/${selectedExam.type}/${selectedExam.id}/take`,
                    currentOrgSlug,
                ) as string,
            );
        }
    };

    const getActionLabel = () => {
        switch (actionType) {
            case 'resume':
                return 'Resume';
            case 'retake':
                return 'Retake';
            default:
                return 'Start';
        }
    };

    return (
        <AppLayout>
            <Head title="My Exams" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <HeadingSmall
                    title="My Exams"
                    description="View and take available exams"
                />

                <div className="space-y-4">
                    <h3 className="text-lg font-semibold">Available Exams</h3>
                    {availableExams.length === 0 ? (
                        <Card>
                            <CardContent className="p-8 text-center">
                                <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
                                <p className="mt-4 text-sm text-muted-foreground">
                                    No exams available at the moment
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2">
                            {availableExams.map((exam) => (
                                <Card
                                    key={exam.id}
                                    className="hover:bg-muted/50"
                                >
                                    <CardContent className="p-6">
                                        <div className="space-y-4">
                                            <div>
                                                <Badge
                                                    variant={
                                                        exam.type ===
                                                        'inservice'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                    className="shrink-0"
                                                >
                                                    {exam.type === 'inservice'
                                                        ? 'In-Service'
                                                        : 'Institution'}
                                                </Badge>
                                            </div>
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="flex-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <h4 className="font-semibold">
                                                            {exam.title}
                                                        </h4>
                                                        {exam.exam_category && (
                                                            <Badge
                                                                variant="outline"
                                                                className="text-xs"
                                                            >
                                                                {
                                                                    exam.exam_category
                                                                }
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    {exam.description && (
                                                        <p className="mt-1 text-sm text-muted-foreground">
                                                            {exam.description}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                <div className="flex items-center gap-1">
                                                    <FileText className="h-4 w-4" />
                                                    <span>
                                                        {exam.questions_count}{' '}
                                                        questions
                                                    </span>
                                                </div>
                                                {exam.duration_minutes && (
                                                    <div className="flex items-center gap-1">
                                                        <Clock className="h-4 w-4" />
                                                        <span>
                                                            {
                                                                exam.duration_minutes
                                                            }{' '}
                                                            min
                                                        </span>
                                                    </div>
                                                )}
                                                <span>
                                                    MPL: {exam.passing_score}
                                                </span>
                                            </div>

                                            {exam.attempt_count > 0 && (
                                                <div className="text-sm">
                                                    <span className="text-muted-foreground">
                                                        Attempts:{' '}
                                                    </span>
                                                    <span className="font-medium">
                                                        {exam.attempt_count}
                                                        {exam.max_attempts &&
                                                            ` / ${exam.max_attempts}`}
                                                    </span>
                                                    {exam.best_score !==
                                                        null && (
                                                        <>
                                                            <span className="mx-2 text-muted-foreground">
                                                                -
                                                            </span>
                                                            <span className="text-muted-foreground">
                                                                Best:{' '}
                                                            </span>
                                                            <span
                                                                className={
                                                                    exam.best_score >=
                                                                    exam.passing_score
                                                                        ? 'font-medium text-green-600 dark:text-green-400'
                                                                        : 'font-medium'
                                                                }
                                                            >
                                                                {
                                                                    exam.best_score
                                                                }
                                                            </span>
                                                        </>
                                                    )}
                                                </div>
                                            )}

                                            {exam.attempt_count > 0 &&
                                            !exam.has_in_progress_attempt ? (
                                                <div className="flex gap-2">
                                                    <Button
                                                        variant="outline"
                                                        className="flex-1"
                                                        onClick={() =>
                                                            handleViewResults(
                                                                exam,
                                                            )
                                                        }
                                                    >
                                                        View Results
                                                    </Button>
                                                    <Button
                                                        className="flex-1"
                                                        onClick={() =>
                                                            handleExamAction(
                                                                exam,
                                                                'retake',
                                                            )
                                                        }
                                                    >
                                                        <Play className="mr-2 h-4 w-4" />
                                                        Retake
                                                    </Button>
                                                </div>
                                            ) : (
                                                <Button
                                                    className="w-full"
                                                    onClick={() =>
                                                        handleExamAction(
                                                            exam,
                                                            exam.has_in_progress_attempt
                                                                ? 'resume'
                                                                : 'start',
                                                        )
                                                    }
                                                >
                                                    <Play className="mr-2 h-4 w-4" />
                                                    {exam.has_in_progress_attempt
                                                        ? 'Resume Exam'
                                                        : 'Start Exam'}
                                                </Button>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>

                {upcomingExams.length > 0 && (
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold">
                            Upcoming Exams
                        </h3>
                        <div className="grid gap-4 md:grid-cols-2">
                            {upcomingExams.map((exam) => (
                                <Card key={exam.id} className="opacity-75">
                                    <CardContent className="p-6">
                                        <div className="space-y-4">
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <h4 className="font-semibold">
                                                        {exam.title}
                                                    </h4>
                                                    {exam.description && (
                                                        <p className="mt-1 text-sm text-muted-foreground">
                                                            {exam.description}
                                                        </p>
                                                    )}
                                                </div>
                                                <Badge variant="outline">
                                                    Upcoming
                                                </Badge>
                                            </div>

                                            <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                <div className="flex items-center gap-1">
                                                    <FileText className="h-4 w-4" />
                                                    <span>
                                                        {exam.questions_count}{' '}
                                                        questions
                                                    </span>
                                                </div>
                                                {exam.duration_minutes && (
                                                    <div className="flex items-center gap-1">
                                                        <Clock className="h-4 w-4" />
                                                        <span>
                                                            {
                                                                exam.duration_minutes
                                                            }{' '}
                                                            min
                                                        </span>
                                                    </div>
                                                )}
                                            </div>

                                            {exam.available_from && (
                                                <p className="text-sm text-muted-foreground">
                                                    Available from:{' '}
                                                    <span className="font-medium">
                                                        {exam.available_from}
                                                    </span>
                                                </p>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                )}

                {completedExams.length > 0 && (
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold">
                            Completed Exams
                        </h3>
                        <div className="grid gap-4 md:grid-cols-2">
                            {completedExams.map((exam) => (
                                <Card key={exam.id}>
                                    <CardContent className="p-6">
                                        <div className="space-y-4">
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <h4 className="font-semibold">
                                                        {exam.title}
                                                    </h4>
                                                    {exam.description && (
                                                        <p className="mt-1 text-sm text-muted-foreground">
                                                            {exam.description}
                                                        </p>
                                                    )}
                                                </div>
                                                <Badge
                                                    variant={
                                                        exam.best_score &&
                                                        exam.best_score >=
                                                            exam.passing_score
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                    className={
                                                        exam.best_score &&
                                                        exam.best_score >=
                                                            exam.passing_score
                                                            ? 'bg-green-600'
                                                            : ''
                                                    }
                                                >
                                                    {exam.best_score &&
                                                    exam.best_score >=
                                                        exam.passing_score
                                                        ? 'Passed'
                                                        : 'Completed'}
                                                </Badge>
                                            </div>

                                            <div className="flex flex-wrap gap-4 text-sm">
                                                {exam.best_score !== null && (
                                                    <div>
                                                        <span className="text-muted-foreground">
                                                            Best Score:{' '}
                                                        </span>
                                                        <span
                                                            className={
                                                                exam.best_score >=
                                                                exam.passing_score
                                                                    ? 'font-medium text-green-600 dark:text-green-400'
                                                                    : 'font-medium'
                                                            }
                                                        >
                                                            {exam.best_score}%
                                                        </span>
                                                    </div>
                                                )}
                                                {exam.attempt_count > 0 && (
                                                    <div>
                                                        <span className="text-muted-foreground">
                                                            Attempts:{' '}
                                                        </span>
                                                        <span className="font-medium">
                                                            {exam.attempt_count}
                                                        </span>
                                                    </div>
                                                )}
                                                {exam.last_attempted && (
                                                    <div className="text-muted-foreground">
                                                        Last:{' '}
                                                        {exam.last_attempted}
                                                    </div>
                                                )}
                                            </div>

                                            <div className="flex gap-2">
                                                <Button
                                                    variant="outline"
                                                    asChild
                                                    className="flex-1"
                                                >
                                                    <Link
                                                        href={preserveOrgParam(
                                                            `/exams/${exam.type}/${exam.id}/results`,
                                                            currentOrgSlug,
                                                        )}
                                                    >
                                                        View Results
                                                    </Link>
                                                </Button>
                                                {(!exam.max_attempts ||
                                                    exam.attempt_count <
                                                        exam.max_attempts) && (
                                                    <Button
                                                        className="flex-1"
                                                        onClick={() =>
                                                            handleExamAction(
                                                                exam,
                                                                'retake',
                                                            )
                                                        }
                                                    >
                                                        <Play className="mr-2 h-4 w-4" />
                                                        Retake
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                )}

                {selectedExam && (
                    <ExamResultsSheet
                        open={showResultsDialog}
                        onOpenChange={setShowResultsDialog}
                        examId={selectedExam.id}
                        examType={selectedExam.type}
                        examTitle={selectedExam.title}
                        onRetake={handleRetakeFromResults}
                    />
                )}

                <AlertDialog
                    open={showStartDialog}
                    onOpenChange={setShowStartDialog}
                >
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle className="flex items-center gap-2">
                                <AlertTriangle className="h-5 w-5 text-amber-500" />
                                {getActionLabel()} Exam Confirmation
                            </AlertDialogTitle>
                            <AlertDialogDescription asChild>
                                <div className="space-y-4">
                                    <p>
                                        You are about to {actionType} the exam:{' '}
                                        <span className="font-semibold text-foreground">
                                            {selectedExam?.title}
                                        </span>
                                    </p>

                                    {(actionType === 'start' ||
                                        actionType === 'retake') && (
                                        <Alert className="border-amber-200 bg-amber-50/80 text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/25 dark:text-amber-100">
                                            <AlertTriangle className="h-4 w-4" />
                                            <AlertTitle>
                                                Monitoring and academic integrity notice
                                            </AlertTitle>
                                            <AlertDescription className="text-amber-800 dark:text-amber-200/90">
                                                <p>
                                                    This exam is closely monitored.
                                                    Browser activity, session changes,
                                                    and suspicious behavior may be
                                                    reviewed by administrators.
                                                </p>
                                                <p>
                                                    Cheating, leaving the exam without
                                                    authorization, or attempting to
                                                    bypass monitoring is not allowed and
                                                    may result in invalidation of your
                                                    attempt.
                                                </p>
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    {selectedExam?.duration_minutes && (
                                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/50 dark:bg-amber-900/20">
                                            <p className="text-sm font-medium text-amber-900 dark:text-amber-200">
                                                Time Limit:{' '}
                                                {selectedExam.duration_minutes}{' '}
                                                minutes
                                            </p>
                                            <p className="mt-1 text-xs text-amber-700 dark:text-amber-300">
                                                Once started, the timer will
                                                begin immediately and cannot be
                                                paused.
                                            </p>
                                        </div>
                                    )}

                                    {actionType === 'resume' ? (
                                        <p className="text-sm text-muted-foreground">
                                            You will continue from where you
                                            left off. Your progress has been
                                            saved.
                                        </p>
                                    ) : (
                                        <div className="space-y-2">
                                            <Label
                                                htmlFor="confirm-text"
                                                className="text-sm font-medium"
                                            >
                                                To confirm, please type{' '}
                                                <span className="font-mono font-bold text-foreground">
                                                    START EXAM
                                                </span>{' '}
                                                below:
                                            </Label>
                                            <Input
                                                id="confirm-text"
                                                value={confirmText}
                                                onChange={(e) =>
                                                    setConfirmText(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Type START EXAM"
                                                className="font-mono"
                                                autoComplete="off"
                                                autoFocus
                                                onKeyDown={(e) => {
                                                    if (
                                                        e.key === 'Enter' &&
                                                        confirmText.toUpperCase() ===
                                                            'START EXAM'
                                                    ) {
                                                        confirmStartExam();
                                                    }
                                                }}
                                            />
                                            {confirmText &&
                                                confirmText.toUpperCase() !==
                                                    'START EXAM' && (
                                                    <p className="text-xs text-destructive">
                                                        Text doesn't match.
                                                        Please type exactly:
                                                        START EXAM
                                                    </p>
                                                )}
                                        </div>
                                    )}
                                </div>
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel
                                onClick={() => setConfirmText('')}
                            >
                                Cancel
                            </AlertDialogCancel>
                            <AlertDialogAction
                                onClick={confirmStartExam}
                                disabled={
                                    actionType !== 'resume' &&
                                    confirmText.toUpperCase() !== 'START EXAM'
                                }
                                className="bg-primary"
                            >
                                {getActionLabel()} Exam
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </AppLayout>
    );
}
