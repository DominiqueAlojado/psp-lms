import { RichTextEditor } from '@/components/rich-text-editor';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

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

export default function CreateNationalAssessment() {
    const [title, setTitle] = useState('');
    const [examYear, setExamYear] = useState(new Date().getFullYear());
    const [examPeriod, setExamPeriod] = useState('Annual');
    const [passingScore, setPassingScore] = useState(0);
    const [duration, setDuration] = useState<number | ''>('');
    const [questions, setQuestions] = useState<DraftQuestion[]>([]);

    const addQuestion = (type: DraftQuestion['question_type']) => {
        const base = { question_type: type, question_text: '', points: 1, difficulty_level: 'medium' as const } as DraftQuestion;
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

    const updateChoice = (qi: number, ci: number, field: keyof QuestionChoice, value: string | boolean) => {
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

    const submit = () => {
        if (!title) {
            alert('Please enter a title');
            return;
        }

        if (questions.length === 0) {
            alert('Please add at least one question');
            return;
        }

        router.post(
            '/in-service',
            {
                title,
                exam_year: examYear,
                exam_period: examPeriod,
                passing_score: passingScore,
                duration_minutes: duration || null,
                randomize_questions: false,
                randomize_choices: false,
                show_results_immediately: false,
                allow_review: false,
                national_ranking_enabled: true,
                institution_comparison_enabled: true,
                questions: questions as never,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.visit('/inservice-exams/active');
                },
                onError: (errors) => {
                    console.error('Error creating national assessment:', errors);
                    alert('Failed to create national assessment. Check console for details.');
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Create National Exam', href: '/in-service/create' }]}>
            <Head title="Create National Exam" />

            <div className="space-y-8 p-6">
                <div className="space-y-2">
                    <Label>Title</Label>
                    <Input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="National exam title" />
                </div>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div className="space-y-2">
                        <Label>Exam Year</Label>
                        <Input
                            type="number"
                            value={examYear}
                            onChange={(e) => setExamYear(parseInt(e.target.value || new Date().getFullYear().toString()))}
                            min={2024}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Exam Period</Label>
                        <Input value={examPeriod} onChange={(e) => setExamPeriod(e.target.value)} placeholder="Annual, Q1, Q2..." />
                    </div>
                    <div className="space-y-2">
                        <Label>Passing Score</Label>
                        <Input
                            type="number"
                            value={passingScore}
                            onChange={(e) => setPassingScore(parseInt(e.target.value || '0'))}
                            min={0}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Duration (minutes)</Label>
                        <Input
                            type="number"
                            value={duration}
                            onChange={(e) => setDuration(e.target.value ? parseInt(e.target.value) : '')}
                            min={1}
                        />
                    </div>
                </div>

                <div className="space-y-4">
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => addQuestion('multiple_choice')}>Add Multiple Choice</Button>
                        <Button variant="outline" onClick={() => addQuestion('multiple_select')}>Add Multiple Select</Button>
                        <Button variant="outline" onClick={() => addQuestion('true_false')}>Add True/False</Button>
                    </div>

                    <div className="space-y-6">
                        {questions.map((q, qi) => (
                            <div key={qi} className="rounded border p-4">
                                <div className="grid gap-2">
                                    <Label>Question ({q.question_type.replace('_', ' ')})</Label>
                                    <RichTextEditor
                                        value={q.question_text}
                                        onChange={(v) => {
                                            setQuestions((prev) => {
                                                const next = [...prev];
                                                next[qi] = { ...next[qi], question_text: v };
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
                                                const v = parseInt(e.target.value || '1');
                                                setQuestions((prev) => {
                                                    const next = [...prev];
                                                    next[qi] = { ...next[qi], points: v };
                                                    return next;
                                                });
                                            }}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Difficulty</Label>
                                        <select
                                            value={q.difficulty_level || 'medium'}
                                            onChange={(e) => {
                                                setQuestions((prev) => {
                                                    const next = [...prev];
                                                    next[qi] = { ...next[qi], difficulty_level: e.target.value as any };
                                                    return next;
                                                });
                                            }}
                                            className="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="easy">Easy</option>
                                            <option value="medium">Medium</option>
                                            <option value="hard">Hard</option>
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Topic</Label>
                                        <Input
                                            value={q.topic || ''}
                                            onChange={(e) => {
                                                setQuestions((prev) => {
                                                    const next = [...prev];
                                                    next[qi] = { ...next[qi], topic: e.target.value };
                                                    return next;
                                                });
                                            }}
                                            placeholder="e.g. Hematology"
                                        />
                                    </div>
                                </div>

                                {(q.question_type === 'multiple_choice' || q.question_type === 'multiple_select') && (
                                    <div className="mt-4 space-y-2">
                                        <Label>Choices</Label>
                                        {(q.choices || []).map((c, ci) => (
                                            <div key={ci} className="flex items-center gap-2">
                                                <Input
                                                    value={c.choice_text}
                                                    onChange={(e) => updateChoice(qi, ci, 'choice_text', e.target.value)}
                                                    placeholder={`Choice #${ci + 1}`}
                                                />
                                                <label className="flex items-center gap-2 text-sm">
                                                    <input
                                                        type="checkbox"
                                                        checked={!!c.is_correct}
                                                        onChange={(e) => updateChoice(qi, ci, 'is_correct', e.target.checked)}
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
                                                        const next = [...prev];
                                                        const choices = [...(next[qi].choices || [])];
                                                        choices.push({ choice_text: '', is_correct: false });
                                                        next[qi] = { ...next[qi], choices };
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
                                                        const next = [...prev];
                                                        next[qi] = { ...next[qi], answer: true };
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
                                                        const next = [...prev];
                                                        next[qi] = { ...next[qi], answer: false };
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
                </div>

                <div className="flex gap-3">
                    <Button onClick={submit}>Save Exam</Button>
                </div>
            </div>
        </AppLayout>
    );
}

