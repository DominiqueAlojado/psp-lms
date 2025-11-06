import { RichTextEditor } from '@/components/rich-text-editor';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';

interface QuestionChoice {
    choice_text: string;
    is_correct: boolean;
}

interface DraftQuestion {
    question_type: 'multiple_choice' | 'multiple_select' | 'true_false';
    question_text: string;
    points: number;
    choices?: QuestionChoice[];
    answer?: boolean;
    order?: number;
}

export default function CreateAssessment() {
    const page = usePage<{ flash: { assessment_id?: number } }>();
    const [title, setTitle] = useState('');
    const [passingScore, setPassingScore] = useState(0);
    const [duration, setDuration] = useState<number | ''>('');
    const [questions, setQuestions] = useState<DraftQuestion[]>([]);

    // Get assessment ID from flash session after creation
    const assessmentId = page.props.flash?.assessment_id || null;

    const addQuestion = (type: DraftQuestion['question_type']) => {
        const base = {
            question_type: type,
            question_text: '',
            points: 1,
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
        if (!title) {
            alert('Please enter a title');
            return;
        }

        // Step 1: Create exam metadata
        router.post(
            '/assessments',
            {
                title,
                passing_score: passingScore,
                duration_minutes: duration || null,
                randomize_questions: false,
                randomize_choices: false,
                show_results_immediately: true,
                allow_review: true,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Assessment ID will be set via useEffect from flash session
                },
                onError: (errors) => {
                         console.error('Error creating exam:', errors);
                    alert('Failed to create exam. Check console for details.');
                },
            },
        );
    };

    const saveQuestions = () => {
        if (!assessmentId) {
            alert('Please create the exam first');
            return;
        }

        if (questions.length === 0) {
            alert('Please add at least one question');
            return;
        }

        // Step 2: Save questions to the created exam
        router.post(
            `/assessments/${assessmentId}/questions`,
            {
                questions: questions as never,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.visit('/institution-exams/active');
                },
                onError: (errors) => {
                    console.error('Error saving questions:', errors);
                    alert(
                        'Failed to save questions. Check console for details.',
                    );
                },
            },
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Create Exam', href: '/institution-exams/create' },
            ]}
        >
            <Head title="Create Exam" />

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
                            placeholder="Exam title"
                            disabled={!!assessmentId}
                        />
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div className="space-y-2">
                            <Label>Passing Score</Label>
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
                                    <div className="mt-3 grid max-w-xs gap-2">
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
