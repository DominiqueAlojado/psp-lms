import { RichTextEditor } from '@/components/rich-text-editor';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { z } from 'zod';

interface QuestionChoice {
    choice_text: string;
    is_correct: boolean;
}

interface DraftQuestion {
    question_type: 'multiple_choice' | 'multiple_select' | 'true_false';
    question_text: string;
    points: number;
    difficulty_level?: 'easy' | 'medium' | 'hard';
    topic?: string;
    order?: number;
    choices?: QuestionChoice[];
    answer?: boolean;
}

const EXAM_CATEGORIES = [
    {
        value: 'anatomic-pathology-theoretical',
        label: 'Anatomic Pathology (Theoretical)',
    },
    {
        value: 'anatomic-pathology-projection',
        label: 'Anatomic Pathology (Projection)',
    },
    {
        value: 'clinical-pathology-theoretical',
        label: 'Clinical Pathology (Theoretical)',
    },
    {
        value: 'clinical-pathology-projection',
        label: 'Clinical Pathology (Projection)',
    },
];

const formSchema = z.object({
    title: z.string().trim().min(1, 'Title is required').max(255),
    exam_year: z.number().int().gte(2000).lte(3000),
    exam_period: z.string().trim().min(1, 'Exam period is required').max(100),
    category: z.enum([
        'anatomic-pathology-theoretical',
        'anatomic-pathology-projection',
        'clinical-pathology-theoretical',
        'clinical-pathology-projection',
    ]),
    passing_score: z.number().int().min(0),
    duration_minutes: z.number().int().min(1).nullable(),
    randomize_questions: z.boolean(),
    randomize_choices: z.boolean(),
    show_results_immediately: z.boolean(),
    allow_review: z.boolean(),
    is_published: z.boolean(),
    national_ranking_enabled: z.boolean(),
    institution_comparison_enabled: z.boolean(),
    scheduled_date: z.string().optional().nullable(),
    results_release_date: z.string().optional().nullable(),
});

const questionSchema = z
    .object({
        question_type: z.enum([
            'multiple_choice',
            'multiple_select',
            'true_false',
        ]),
        question_text: z.string().min(1, 'Question text is required'),
        points: z.number().int().min(1),
        difficulty_level: z.enum(['easy', 'medium', 'hard']).optional(),
        topic: z.string().max(255).optional(),
        // For MCQ/MS, require choices; for TF, require answer
        choices: z
            .array(
                z.object({
                    choice_text: z.string().min(1, 'Choice text is required'),
                    is_correct: z.boolean(),
                }),
            )
            .optional(),
        answer: z.boolean().optional(),
    })
    .superRefine((q, ctx) => {
        if (
            q.question_type === 'multiple_choice' ||
            q.question_type === 'multiple_select'
        ) {
            if (!q.choices || q.choices.length < 2) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: 'At least two choices are required',
                    path: ['choices'],
                });
            }
            // Should have at least one correct choice
            if (!q.choices?.some((c) => c.is_correct)) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: 'Mark at least one correct choice',
                    path: ['choices'],
                });
            }
        }
        if (q.question_type === 'true_false' && typeof q.answer !== 'boolean') {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: 'Select True or False',
                path: ['answer'],
            });
        }
    });

const questionsPayloadSchema = z.object({
    questions: z.array(questionSchema).min(1, 'Add at least one question'),
});

export default function CreateNationalAssessment() {
    const page = usePage<{ flash: { assessment_id?: number } }>();
    const [title, setTitle] = useState('');
    const [examYear, setExamYear] = useState(new Date().getFullYear());
    const [examPeriod, setExamPeriod] = useState('Annual');
    const [passingScore, setPassingScore] = useState(0);
    const [duration, setDuration] = useState<number | ''>('');
    const [examCategory, setExamCategory] = useState<string>(
        EXAM_CATEGORIES[0].value,
    );
    const [randomizeQuestions, setRandomizeQuestions] = useState(false);
    const [randomizeChoices, setRandomizeChoices] = useState(false);
    const [showResultsImmediately, setShowResultsImmediately] = useState(true);
    const [allowReview, setAllowReview] = useState(true);
    const [isPublished, setIsPublished] = useState(false);
    const [scheduledDate, setScheduledDate] = useState<string>('');
    const [resultsReleaseDate, setResultsReleaseDate] = useState<string>('');
    const [questions, setQuestions] = useState<DraftQuestion[]>([]);

    // Get assessment ID from flash session after creation
    const assessmentId = page.props.flash?.assessment_id || null;

    const addQuestion = (type: DraftQuestion['question_type']) => {
        const base = {
            question_type: type,
            question_text: '',
            points: 1,
            difficulty_level: 'medium' as const,
        } as DraftQuestion;
        if (type === 'multiple_choice' || type === 'multiple_select') {
            base.choices = [
                { choice_text: '', is_correct: false },
                { choice_text: '', is_correct: false },
            ];
        }
        if (type === 'true_false') {
            base.answer = true;
        }
        setQuestions((q) => [...q, base]);
    };

    const updateChoice = (
        qi: number,
        ci: number,
        field: keyof QuestionChoice,
        value: string | boolean,
    ) => {
        setQuestions((prev) => {
            const next = [...prev];
            const q = next[qi];
            if (!q.choices) return prev;
            const choices = [...q.choices];
            choices[ci] = { ...choices[ci], [field]: value };
            next[qi] = { ...q, choices };
            return next;
        });
    };

    const createExam = () => {
        const payload = {
            title,
            exam_year: examYear,
            exam_period: examPeriod,
            passing_score: passingScore,
            duration_minutes: duration || null,
            randomize_questions: randomizeQuestions,
            randomize_choices: randomizeChoices,
            show_results_immediately: showResultsImmediately,
            allow_review: allowReview,
            is_published: isPublished,
            national_ranking_enabled: true,
            institution_comparison_enabled: true,
            category: examCategory || null,
            scheduled_date: scheduledDate || null,
            results_release_date: resultsReleaseDate || null,
        };

        const parsed = formSchema.safeParse(payload);
        if (!parsed.success) {
            const first = parsed.error.issues[0];
            toast.error(first.message || 'Please check the form fields.');
            return;
        }

        router.post('/inservice-exams', parsed.data, {
            preserveScroll: true,
            onSuccess: (page: {
                props?: { flash?: { assessment_id?: number } };
            }) => {
                const id = page.props?.flash?.assessment_id;
                if (id) {
                    router.visit(`/inservice-exams/${id}/edit`);
                }
            },
            onError: (errors) => {
                console.error('Error creating national exam:', errors);
                toast.error('Failed to create national exam');
            },
        });
    };

    const saveQuestions = () => {
        if (!assessmentId) {
            alert('Please create the exam first');
            return;
        }

        const prepared = {
            questions: questions.map((q) => ({
                ...q,
                // Ensure numeric types for zod
                points: Number(q.points),
            })),
        };
        const parsed = questionsPayloadSchema.safeParse(prepared);
        if (!parsed.success) {
            const first = parsed.error.issues[0];
            toast.error(first.message || 'Please check your questions.');
            return;
        }

        router.post(
            `/inservice-exams/${assessmentId}/questions`,
            parsed.data as never,
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.visit('/inservice-exams/active');
                },
                onError: (errors) => {
                    console.error('Error saving questions:', errors);
                    toast.error('Failed to save questions');
                },
            },
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Create National Exam',
                    href: '/inservice-exams/create',
                },
            ]}
        >
            <Head title="Create National Exam" />

            <div className="space-y-8 p-6">
                {/* Step 1: Exam Metadata */}
                <div className="space-y-4 rounded-lg border p-6">
                    <h3 className="text-lg font-semibold">
                        Step 1: Exam Details
                    </h3>
                    <div className="space-y-2">
                        <Label>Title</Label>
                        <Input
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            placeholder="National exam title"
                            disabled={!!assessmentId}
                        />
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div className="space-y-2">
                            <Label>Exam Year</Label>
                            <Input
                                type="number"
                                value={examYear}
                                onChange={(e) =>
                                    setExamYear(
                                        parseInt(
                                            e.target.value ||
                                                new Date()
                                                    .getFullYear()
                                                    .toString(),
                                        ),
                                    )
                                }
                                min={2024}
                                disabled={!!assessmentId}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Exam Period</Label>
                            <Input
                                value={examPeriod}
                                onChange={(e) => setExamPeriod(e.target.value)}
                                placeholder="Annual, Q1, Q2..."
                                disabled={!!assessmentId}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Exam Category</Label>
                            <select
                                value={examCategory}
                                onChange={(e) =>
                                    setExamCategory(e.target.value)
                                }
                                disabled={!!assessmentId}
                                className="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                {EXAM_CATEGORIES.map((c) => (
                                    <option key={c.value} value={c.value}>
                                        {c.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>MPL</Label>
                            <Input
                                type="number"
                                value={passingScore}
                                onChange={(e) =>
                                    setPassingScore(
                                        parseInt(e.target.value || '0'),
                                    )
                                }
                                min={0}
                                disabled={!!assessmentId}
                            />
                            <p className="text-sm text-muted-foreground">
                                Minimum Passing Level in raw points/items.
                            </p>
                        </div>
                        <div className="space-y-2">
                            <Label>Duration (minutes)</Label>
                            <Input
                                type="number"
                                value={duration}
                                onChange={(e) =>
                                    setDuration(
                                        e.target.value
                                            ? parseInt(e.target.value)
                                            : '',
                                    )
                                }
                                min={1}
                                disabled={!!assessmentId}
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Available From</Label>
                            <Input
                                type="datetime-local"
                                value={scheduledDate}
                                onChange={(e) =>
                                    setScheduledDate(e.target.value)
                                }
                                disabled={!!assessmentId}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Available Until</Label>
                            <Input
                                type="datetime-local"
                                value={resultsReleaseDate}
                                onChange={(e) =>
                                    setResultsReleaseDate(e.target.value)
                                }
                                disabled={!!assessmentId}
                            />
                        </div>
                    </div>
                    <div className="space-y-3">
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={randomizeQuestions}
                                onChange={(e) =>
                                    setRandomizeQuestions(e.target.checked)
                                }
                                disabled={!!assessmentId}
                            />
                            Randomize questions
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={randomizeChoices}
                                onChange={(e) =>
                                    setRandomizeChoices(e.target.checked)
                                }
                                disabled={!!assessmentId}
                            />
                            Randomize answer choices
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={showResultsImmediately}
                                onChange={(e) =>
                                    setShowResultsImmediately(e.target.checked)
                                }
                                disabled={!!assessmentId}
                            />
                            Show results immediately after submission
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={allowReview}
                                onChange={(e) =>
                                    setAllowReview(e.target.checked)
                                }
                                disabled={!!assessmentId}
                            />
                            Allow residents to review answers
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={isPublished}
                                onChange={(e) =>
                                    setIsPublished(e.target.checked)
                                }
                                disabled={!!assessmentId}
                            />
                            Publish exam (make it visible to residents)
                        </label>
                    </div>
                    {!assessmentId && (
                        <Button onClick={createExam}>Create Exam</Button>
                    )}
                    {assessmentId && (
                        <div className="rounded bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                            ✓ Exam created! Now add questions below.
                        </div>
                    )}
                </div>

                {/* Step 2: Questions (only shown after exam is created) */}
                {assessmentId && (
                    <div className="space-y-4">
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                onClick={() => addQuestion('multiple_choice')}
                            >
                                Add Multiple Choice
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => addQuestion('multiple_select')}
                            >
                                Add Multiple Select
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => addQuestion('true_false')}
                            >
                                Add True/False
                            </Button>
                        </div>

                        <div className="space-y-6">
                            {questions.map((q, qi) => (
                                <div key={qi} className="rounded border p-4">
                                    <div className="mb-3 flex items-center justify-between">
                                        <Label>
                                            Question #{qi + 1} (
                                            {q.question_type.replace('_', ' ')})
                                        </Label>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => {
                                                setQuestions((prev) =>
                                                    prev.filter(
                                                        (_, i) => i !== qi,
                                                    ),
                                                );
                                            }}
                                        >
                                            <Trash2 className="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                    <div className="grid gap-2">
                                        <RichTextEditor
                                            value={q.question_text}
                                            onChange={(v) => {
                                                setQuestions((prev) => {
                                                    const next = [...prev];
                                                    next[qi] = {
                                                        ...next[qi],
                                                        question_text: v,
                                                    };
                                                    return next;
                                                });
                                            }}
                                            placeholder="Type the question"
                                        />
                                    </div>
                                    <div className="mt-3 grid grid-cols-3 gap-2">
                                        <div className="space-y-2">
                                            <Label>Points</Label>
                                            <Input
                                                type="number"
                                                value={q.points}
                                                min={1}
                                                onChange={(e) => {
                                                    const v = parseInt(
                                                        e.target.value || '1',
                                                    );
                                                    setQuestions((prev) => {
                                                        const next = [...prev];
                                                        next[qi] = {
                                                            ...next[qi],
                                                            points: v,
                                                        };
                                                        return next;
                                                    });
                                                }}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Difficulty</Label>
                                            <select
                                                value={
                                                    q.difficulty_level ||
                                                    'medium'
                                                }
                                                onChange={(e) => {
                                                    setQuestions((prev) => {
                                                        const next = [...prev];
                                                        next[qi] = {
                                                            ...next[qi],
                                                            difficulty_level: e
                                                                .target
                                                                .value as
                                                                | 'easy'
                                                                | 'medium'
                                                                | 'hard',
                                                        };
                                                        return next;
                                                    });
                                                }}
                                                className="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            >
                                                <option value="easy">
                                                    Easy
                                                </option>
                                                <option value="medium">
                                                    Medium
                                                </option>
                                                <option value="hard">
                                                    Hard
                                                </option>
                                            </select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Topic</Label>
                                            <Input
                                                value={q.topic || ''}
                                                onChange={(e) => {
                                                    setQuestions((prev) => {
                                                        const next = [...prev];
                                                        next[qi] = {
                                                            ...next[qi],
                                                            topic: e.target
                                                                .value,
                                                        };
                                                        return next;
                                                    });
                                                }}
                                                placeholder="e.g. Hematology"
                                            />
                                        </div>
                                    </div>

                                    {(q.question_type === 'multiple_choice' ||
                                        q.question_type ===
                                            'multiple_select') && (
                                        <div className="mt-4 space-y-2">
                                            <Label>Choices</Label>
                                            {(q.choices || []).map((c, ci) => (
                                                <div
                                                    key={ci}
                                                    className="flex items-center gap-2"
                                                >
                                                    <Input
                                                        value={c.choice_text}
                                                        onChange={(e) =>
                                                            updateChoice(
                                                                qi,
                                                                ci,
                                                                'choice_text',
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder={`Choice #${ci + 1}`}
                                                    />
                                                    <label className="flex items-center gap-2 text-sm">
                                                        <input
                                                            type="checkbox"
                                                            checked={
                                                                !!c.is_correct
                                                            }
                                                            onChange={(e) =>
                                                                updateChoice(
                                                                    qi,
                                                                    ci,
                                                                    'is_correct',
                                                                    e.target
                                                                        .checked,
                                                                )
                                                            }
                                                        />
                                                        Correct
                                                    </label>
                                                </div>
                                            ))}
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="ghost"
                                                    onClick={() =>
                                                        setQuestions((prev) => {
                                                            const next = [
                                                                ...prev,
                                                            ];
                                                            const choices = [
                                                                ...(next[qi]
                                                                    .choices ||
                                                                    []),
                                                            ];
                                                            choices.push({
                                                                choice_text: '',
                                                                is_correct: false,
                                                            });
                                                            next[qi] = {
                                                                ...next[qi],
                                                                choices,
                                                            };
                                                            return next;
                                                        })
                                                    }
                                                >
                                                    Add Choice
                                                </Button>
                                            </div>
                                        </div>
                                    )}

                                    {q.question_type === 'true_false' && (
                                        <div className="mt-4">
                                            <label className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="radio"
                                                    name={`tf-${qi}`}
                                                    checked={q.answer === true}
                                                    onChange={() =>
                                                        setQuestions((prev) => {
                                                            const next = [
                                                                ...prev,
                                                            ];
                                                            next[qi] = {
                                                                ...next[qi],
                                                                answer: true,
                                                            };
                                                            return next;
                                                        })
                                                    }
                                                />
                                                True
                                            </label>
                                            <label className="mt-2 flex items-center gap-2 text-sm">
                                                <input
                                                    type="radio"
                                                    name={`tf-${qi}`}
                                                    checked={q.answer === false}
                                                    onChange={() =>
                                                        setQuestions((prev) => {
                                                            const next = [
                                                                ...prev,
                                                            ];
                                                            next[qi] = {
                                                                ...next[qi],
                                                                answer: false,
                                                            };
                                                            return next;
                                                        })
                                                    }
                                                />
                                                False
                                            </label>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                        <div className="flex gap-3">
                            <Button onClick={saveQuestions}>
                                Save Questions
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
