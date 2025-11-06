import { RichTextEditor } from '@/components/rich-text-editor';
import { TopicSelector } from '@/components/topic-selector';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Check,
    ChevronDown,
    ChevronRight,
    Save,
    Search,
    Trash2,
    Upload,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Topic {
    id: number;
    name: string;
    slug: string;
    is_global: boolean;
}

interface QuestionChoice {
    id?: number;
    choice_text: string;
    is_correct: boolean;
    order?: number;
}

interface DraftQuestion {
    id?: number;
    topic_id?: number | null;
    question_type: 'multiple_choice' | 'multiple_select' | 'true_false';
    question_text: string;
    points: number;
    choices?: QuestionChoice[];
    answer?: boolean;
    order?: number;
    image?: string; // base64 encoded image
    image_url?: string; // for preview
    image_path?: string; // existing image path
}

interface Assessment {
    id: number;
    title: string;
    description: string | null;
    duration_minutes: number | null;
    total_points: number;
    passing_score: number;
    randomize_questions: boolean;
    randomize_choices: boolean;
    show_results_immediately: boolean;
    allow_review: boolean;
    is_published: boolean;
    available_from: string | null;
    available_until: string | null;
    questions: DraftQuestion[];
    created_by: string;
    created_at: string;
}

interface PageProps {
    assessment: Assessment;
}

export default function EditAssessment() {
    const { assessment } = usePage<PageProps>().props;

    const [title, setTitle] = useState(assessment.title);
    const [description, setDescription] = useState(
        assessment.description || '',
    );
    const [passingScore, setPassingScore] = useState(assessment.passing_score);
    const [duration, setDuration] = useState<number | ''>(
        assessment.duration_minutes || '',
    );
    const [randomizeQuestions, setRandomizeQuestions] = useState(
        assessment.randomize_questions,
    );
    const [randomizeChoices, setRandomizeChoices] = useState(
        assessment.randomize_choices,
    );
    const [showResultsImmediately, setShowResultsImmediately] = useState(
        assessment.show_results_immediately,
    );
    const [allowReview, setAllowReview] = useState(assessment.allow_review);
    const [isPublished, setIsPublished] = useState(assessment.is_published);
    const [availableFrom, setAvailableFrom] = useState(
        assessment.available_from || '',
    );
    const [availableUntil, setAvailableUntil] = useState(
        assessment.available_until || '',
    );
    const [questions, setQuestions] = useState<DraftQuestion[]>(
        assessment.questions || [],
    );
    const [openQuestions, setOpenQuestions] = useState<Record<number, boolean>>(
        {},
    );
    const [savingQuestion, setSavingQuestion] = useState<number | null>(null);
    const [topics, setTopics] = useState<Topic[]>([]);
    const [questionSearchQuery, setQuestionSearchQuery] = useState('');

    // Fetch topics once on page load
    useEffect(() => {
        fetch('/topics')
            .then((res) => res.json())
            .then((data) => setTopics(data))
            .catch((err) => console.error('Failed to fetch topics:', err));
    }, []);

    const toggleQuestion = (index: number) => {
        setOpenQuestions((prev) => ({
            ...prev,
            [index]: !prev[index],
        }));
    };

    const addQuestion = (type: DraftQuestion['question_type']) => {
        const base = {
            question_type: type,
            question_text: '',
            points: 1,
            order: questions.length, // Set order as the next number
        } as DraftQuestion;
        if (type === 'multiple_choice' || type === 'multiple_select') {
            base.choices = [
                { choice_text: '', is_correct: true },
                { choice_text: '', is_correct: false },
                { choice_text: '', is_correct: false },
                { choice_text: '', is_correct: false },
            ];
        }
        if (type === 'true_false') {
            base.answer = true;
        }
        // Add new question at the bottom for proper ordering
        setQuestions((q) => [...q, base]);
        // Auto-open the newly added question
        const newIndex = questions.length;
        setOpenQuestions((prev) => ({ ...prev, [newIndex]: true }));

        // Scroll to the new question after a brief delay
        setTimeout(() => {
            const questionElements = document.querySelectorAll(
                '[data-question-index]',
            );
            const newQuestionElement = questionElements[newIndex];
            if (newQuestionElement) {
                newQuestionElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
            }
        }, 100);
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

    const handleImageUpload = (qi: number, file: File) => {
        const reader = new FileReader();
        reader.onloadend = () => {
            const base64String = reader.result as string;
            setQuestions((prev) => {
                const next = [...prev];
                next[qi] = {
                    ...next[qi],
                    image: base64String,
                    image_url: base64String,
                    image_path: undefined, // Clear old path since we have new image
                };
                return next;
            });
        };
        reader.readAsDataURL(file);
    };

    const removeImage = (qi: number) => {
        setQuestions((prev) => {
            const next = [...prev];
            next[qi] = {
                ...next[qi],
                image: undefined,
                image_url: undefined,
                image_path: undefined,
            };
            return next;
        });
    };

    const updateExam = () => {
        if (!title) {
            toast.error('Please enter a title');
            return;
        }

        router.patch(
            `/assessments/${assessment.id}`,
            {
                title,
                description: description || null,
                passing_score: passingScore,
                duration_minutes: duration || null,
                randomize_questions: randomizeQuestions,
                randomize_choices: randomizeChoices,
                show_results_immediately: showResultsImmediately,
                allow_review: allowReview,
                is_published: isPublished,
                available_from: availableFrom || null,
                available_until: availableUntil || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Exam updated successfully!');
                },
                onError: (errors) => {
                    console.error('Error updating exam:', errors);
                    toast.error('Failed to update exam');
                },
            },
        );
    };

    const saveOneQuestion = (qi: number) => {
        const question = questions[qi];
        if (!question.question_text) {
            toast.error('Please enter question text');
            return;
        }

        setSavingQuestion(qi);

        router.post(
            `/assessments/${assessment.id}/questions/save-one`,
            question as any,
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page: any) => {
                    const savedQuestion = page.props.flash?.question;
                    if (savedQuestion) {
                        setQuestions((prev) => {
                            const next = [...prev];
                            next[qi] = {
                                ...next[qi],
                                id: savedQuestion.id,
                            };
                            return next;
                        });
                    }
                    setSavingQuestion(null);
                    toast.success('Question saved successfully!');
                },
                onError: (errors) => {
                    console.error('Error saving question:', errors);
                    toast.error('Failed to save question');
                    setSavingQuestion(null);
                },
            },
        );
    };

    const deleteOneQuestion = (qi: number) => {
        const question = questions[qi];

        if (question.id) {
            // Delete from database
            if (!confirm('Are you sure you want to delete this question?')) {
                return;
            }

            router.delete(
                `/assessments/${assessment.id}/questions/${question.id}`,
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        setQuestions((prev) => prev.filter((_, i) => i !== qi));
                        toast.success('Question deleted successfully!');
                    },
                    onError: (errors) => {
                        console.error('Error deleting question:', errors);
                        toast.error('Failed to delete question');
                    },
                },
            );
        } else {
            // Just remove from local state (not saved yet)
            setQuestions((prev) => prev.filter((_, i) => i !== qi));
            toast.success('Question removed');
        }
    };

    // Filter questions based on search query
    const filteredQuestions = questions.filter((q, qi) => {
        if (!questionSearchQuery) return true;

        const query = questionSearchQuery.toLowerCase();
        const questionText = q.question_text?.toLowerCase() || '';
        const choicesText =
            q.choices?.map((c) => c.choice_text.toLowerCase()).join(' ') || '';
        const topicName =
            topics.find((t) => t.id === q.topic_id)?.name.toLowerCase() || '';
        const questionNumber = `#${qi + 1}`;

        return (
            questionText.includes(query) ||
            choicesText.includes(query) ||
            topicName.includes(query) ||
            questionNumber.includes(query)
        );
    });

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Institution Exams',
                    href: '/institution-exams/active',
                },
                { title: 'Edit Exam', href: '#' },
            ]}
        >
            <Head title="Edit Exam" />

            <div className="space-y-8 p-6">
                {/* Exam Metadata */}
                <div className="space-y-4 rounded-lg border p-6">
                    <h3 className="text-lg font-semibold">Exam Details</h3>
                    <div className="space-y-2">
                        <Label>Title</Label>
                        <Input
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            placeholder="Exam title"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Description</Label>
                        <Textarea
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="Exam description (optional)"
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
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Available From</Label>
                            <Input
                                type="datetime-local"
                                value={availableFrom}
                                onChange={(e) =>
                                    setAvailableFrom(e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Available Until</Label>
                            <Input
                                type="datetime-local"
                                value={availableUntil}
                                onChange={(e) =>
                                    setAvailableUntil(e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-3">
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="randomize-questions"
                                checked={randomizeQuestions}
                                onCheckedChange={(checked) =>
                                    setRandomizeQuestions(checked as boolean)
                                }
                            />
                            <Label htmlFor="randomize-questions">
                                Randomize questions
                            </Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="randomize-choices"
                                checked={randomizeChoices}
                                onCheckedChange={(checked) =>
                                    setRandomizeChoices(checked as boolean)
                                }
                            />
                            <Label htmlFor="randomize-choices">
                                Randomize answer choices
                            </Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="show-results"
                                checked={showResultsImmediately}
                                onCheckedChange={(checked) =>
                                    setShowResultsImmediately(
                                        checked as boolean,
                                    )
                                }
                            />
                            <Label htmlFor="show-results">
                                Show results immediately after submission
                            </Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="allow-review"
                                checked={allowReview}
                                onCheckedChange={(checked) =>
                                    setAllowReview(checked as boolean)
                                }
                            />
                            <Label htmlFor="allow-review">
                                Allow residents to review answers
                            </Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is-published"
                                checked={isPublished}
                                onCheckedChange={(checked) =>
                                    setIsPublished(checked as boolean)
                                }
                            />
                            <Label htmlFor="is-published">
                                Publish exam (make it visible to residents)
                            </Label>
                        </div>
                    </div>
                    <Button onClick={updateExam}>Update Exam Details</Button>
                </div>

                {/* Questions */}
                <div className="space-y-4 rounded-lg border p-6">
                    <h3 className="text-lg font-semibold">Questions</h3>
                    <div className="flex items-center justify-between gap-2">
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
                        {questions.length > 0 && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                    const allOpen =
                                        Object.values(openQuestions).every(
                                            Boolean,
                                        );
                                    const newState: Record<number, boolean> =
                                        {};
                                    questions.forEach((_, i) => {
                                        newState[i] = !allOpen;
                                    });
                                    setOpenQuestions(newState);
                                }}
                            >
                                {Object.values(openQuestions).every(Boolean)
                                    ? 'Collapse All'
                                    : 'Expand All'}
                            </Button>
                        )}
                    </div>

                    {/* Search Questions */}
                    {questions.length > 0 && (
                        <div className="relative">
                            <div className="pointer-events-none absolute top-3 left-3 h-4 w-4">
                                <Search className="h-4 w-4 text-muted-foreground" />
                            </div>
                            <Input
                                type="text"
                                placeholder="Search questions by text, choices, topic, or number..."
                                value={questionSearchQuery}
                                onChange={(e) =>
                                    setQuestionSearchQuery(e.target.value)
                                }
                                className="pl-10"
                            />
                            {questionSearchQuery && (
                                <div className="mt-2 text-sm text-muted-foreground">
                                    Showing {filteredQuestions.length} of{' '}
                                    {questions.length} questions
                                </div>
                            )}
                        </div>
                    )}

                    <div className="space-y-6">
                        {filteredQuestions.length === 0 &&
                        questionSearchQuery ? (
                            <div className="rounded-lg border p-6 text-center">
                                <Search className="mx-auto h-10 w-10 text-muted-foreground" />
                                <p className="mt-2 text-sm text-muted-foreground">
                                    No questions found matching "
                                    {questionSearchQuery}"
                                </p>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="mt-2"
                                    onClick={() => setQuestionSearchQuery('')}
                                >
                                    Clear search
                                </Button>
                            </div>
                        ) : (
                            filteredQuestions.map((q, filteredIndex) => {
                                // Get the original index in the full questions array
                                const qi = questions.indexOf(q);
                                return (
                                    <Collapsible
                                        key={qi}
                                        data-question-index={qi}
                                        open={openQuestions[qi] ?? false}
                                        onOpenChange={() => toggleQuestion(qi)}
                                        className="rounded border"
                                    >
                                        <div className="flex items-center justify-between border-b bg-muted/50 p-3">
                                            <CollapsibleTrigger className="flex flex-1 items-center gap-2 text-left">
                                                {openQuestions[qi] ? (
                                                    <ChevronDown className="h-4 w-4" />
                                                ) : (
                                                    <ChevronRight className="h-4 w-4" />
                                                )}
                                                <Label className="cursor-pointer font-semibold">
                                                    Question #{qi + 1} (
                                                    {q.question_type.replace(
                                                        '_',
                                                        ' ',
                                                    )}
                                                    )
                                                    {q.id && (
                                                        <span className="ml-2 text-xs text-green-600 dark:text-green-400">
                                                            <Check className="inline h-3 w-3" />{' '}
                                                            Saved
                                                        </span>
                                                    )}
                                                    {q.question_text && (
                                                        <span className="ml-2 font-normal text-muted-foreground">
                                                            -{' '}
                                                            {q.question_text
                                                                .substring(
                                                                    0,
                                                                    50,
                                                                )
                                                                .replace(
                                                                    /<[^>]*>/g,
                                                                    '',
                                                                )}
                                                            {q.question_text
                                                                .length > 50
                                                                ? '...'
                                                                : ''}
                                                        </span>
                                                    )}
                                                </Label>
                                            </CollapsibleTrigger>
                                            <div className="flex items-center gap-1">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        saveOneQuestion(qi)
                                                    }
                                                    disabled={
                                                        savingQuestion === qi
                                                    }
                                                >
                                                    <Save className="h-4 w-4 text-green-600" />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        deleteOneQuestion(qi)
                                                    }
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </div>
                                        <CollapsibleContent className="p-4">
                                            <div className="grid gap-4">
                                                {/* Topic Selection */}
                                                <TopicSelector
                                                    value={q.topic_id}
                                                    onChange={(topicId) => {
                                                        setQuestions((prev) => {
                                                            const next = [
                                                                ...prev,
                                                            ];
                                                            next[qi] = {
                                                                ...next[qi],
                                                                topic_id:
                                                                    topicId,
                                                            };
                                                            return next;
                                                        });
                                                    }}
                                                    preloadedTopics={topics}
                                                    onTopicsUpdated={setTopics}
                                                />

                                                {/* Question Text */}
                                                <div className="grid gap-2">
                                                    <Label>Question Text</Label>
                                                    <RichTextEditor
                                                        value={q.question_text}
                                                        onChange={(v) => {
                                                            setQuestions(
                                                                (prev) => {
                                                                    const next =
                                                                        [
                                                                            ...prev,
                                                                        ];
                                                                    next[qi] = {
                                                                        ...next[
                                                                            qi
                                                                        ],
                                                                        question_text:
                                                                            v,
                                                                    };
                                                                    return next;
                                                                },
                                                            );
                                                        }}
                                                        placeholder="Type the question"
                                                    />
                                                </div>
                                            </div>
                                            <div className="mt-3 space-y-3">
                                                <Label>
                                                    Question Image (Optional)
                                                </Label>
                                                {q.image_url || q.image_path ? (
                                                    <div className="relative flex">
                                                        <img
                                                            src={
                                                                q.image_url ||
                                                                q.image_path
                                                            }
                                                            alt="Question"
                                                            className="h-auto max-h-96 w-full max-w-2xl rounded border object-contain"
                                                        />
                                                        <Button
                                                            type="button"
                                                            variant="destructive"
                                                            size="sm"
                                                            className="absolute top-2 right-2"
                                                            onClick={() =>
                                                                removeImage(qi)
                                                            }
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <div className="flex items-center gap-2">
                                                        <Input
                                                            type="file"
                                                            accept="image/*"
                                                            onChange={(e) => {
                                                                const file =
                                                                    e.target
                                                                        .files?.[0];
                                                                if (file) {
                                                                    handleImageUpload(
                                                                        qi,
                                                                        file,
                                                                    );
                                                                }
                                                            }}
                                                            className="max-w-xs"
                                                        />
                                                        <Upload className="h-4 w-4 text-muted-foreground" />
                                                    </div>
                                                )}
                                            </div>
                                            <div className="mt-3 grid max-w-xs gap-2">
                                                <Label>Points</Label>
                                                <Input
                                                    type="number"
                                                    value={q.points}
                                                    min={1}
                                                    onChange={(e) => {
                                                        const v = parseInt(
                                                            e.target.value ||
                                                                '1',
                                                        );
                                                        setQuestions((prev) => {
                                                            const next = [
                                                                ...prev,
                                                            ];
                                                            next[qi] = {
                                                                ...next[qi],
                                                                points: v,
                                                            };
                                                            return next;
                                                        });
                                                    }}
                                                />
                                            </div>

                                            {(q.question_type ===
                                                'multiple_choice' ||
                                                q.question_type ===
                                                    'multiple_select') && (
                                                <div className="mt-4 space-y-2">
                                                    <Label>
                                                        Choices (Choice #1 is
                                                        the correct answer)
                                                    </Label>
                                                    {(q.choices || []).map(
                                                        (c, ci) => (
                                                            <div
                                                                key={ci}
                                                                className="flex items-center gap-2"
                                                            >
                                                                <div className="flex w-full items-center gap-2">
                                                                    <span
                                                                        className={`min-w-[80px] text-sm font-medium ${ci === 0 ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'}`}
                                                                    >
                                                                        Choice #
                                                                        {ci + 1}
                                                                        {ci ===
                                                                        0
                                                                            ? ' ✓'
                                                                            : ''}
                                                                    </span>
                                                                    <Input
                                                                        value={
                                                                            c.choice_text
                                                                        }
                                                                        onChange={(
                                                                            e,
                                                                        ) => {
                                                                            setQuestions(
                                                                                (
                                                                                    prev,
                                                                                ) => {
                                                                                    const next =
                                                                                        [
                                                                                            ...prev,
                                                                                        ];
                                                                                    const choices =
                                                                                        [
                                                                                            ...(next[
                                                                                                qi
                                                                                            ]
                                                                                                .choices ||
                                                                                                []),
                                                                                        ];
                                                                                    choices[
                                                                                        ci
                                                                                    ] =
                                                                                        {
                                                                                            ...choices[
                                                                                                ci
                                                                                            ],
                                                                                            choice_text:
                                                                                                e
                                                                                                    .target
                                                                                                    .value,
                                                                                            is_correct:
                                                                                                ci ===
                                                                                                0,
                                                                                        };
                                                                                    next[
                                                                                        qi
                                                                                    ] =
                                                                                        {
                                                                                            ...next[
                                                                                                qi
                                                                                            ],
                                                                                            choices,
                                                                                        };
                                                                                    return next;
                                                                                },
                                                                            );
                                                                        }}
                                                                        placeholder={`Enter choice ${ci + 1}`}
                                                                        className={
                                                                            ci ===
                                                                            0
                                                                                ? 'border-green-500'
                                                                                : ''
                                                                        }
                                                                    />
                                                                </div>
                                                            </div>
                                                        ),
                                                    )}
                                                    {(q.choices?.length || 0) <
                                                        4 && (
                                                        <div className="flex gap-2">
                                                            <Button
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    setQuestions(
                                                                        (
                                                                            prev,
                                                                        ) => {
                                                                            const next =
                                                                                [
                                                                                    ...prev,
                                                                                ];
                                                                            const choices =
                                                                                [
                                                                                    ...(next[
                                                                                        qi
                                                                                    ]
                                                                                        .choices ||
                                                                                        []),
                                                                                ];
                                                                            if (
                                                                                choices.length <
                                                                                4
                                                                            ) {
                                                                                choices.push(
                                                                                    {
                                                                                        choice_text:
                                                                                            '',
                                                                                        is_correct: false,
                                                                                    },
                                                                                );
                                                                            }
                                                                            next[
                                                                                qi
                                                                            ] =
                                                                                {
                                                                                    ...next[
                                                                                        qi
                                                                                    ],
                                                                                    choices,
                                                                                };
                                                                            return next;
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                Add Choice
                                                            </Button>
                                                        </div>
                                                    )}
                                                </div>
                                            )}

                                            {q.question_type ===
                                                'true_false' && (
                                                <div className="mt-4">
                                                    <label className="flex items-center gap-2 text-sm">
                                                        <input
                                                            type="radio"
                                                            name={`tf-${qi}`}
                                                            checked={
                                                                q.answer ===
                                                                true
                                                            }
                                                            onChange={() =>
                                                                setQuestions(
                                                                    (prev) => {
                                                                        const next =
                                                                            [
                                                                                ...prev,
                                                                            ];
                                                                        next[
                                                                            qi
                                                                        ] = {
                                                                            ...next[
                                                                                qi
                                                                            ],
                                                                            answer: true,
                                                                        };
                                                                        return next;
                                                                    },
                                                                )
                                                            }
                                                        />
                                                        True
                                                    </label>
                                                    <label className="mt-2 flex items-center gap-2 text-sm">
                                                        <input
                                                            type="radio"
                                                            name={`tf-${qi}`}
                                                            checked={
                                                                q.answer ===
                                                                false
                                                            }
                                                            onChange={() =>
                                                                setQuestions(
                                                                    (prev) => {
                                                                        const next =
                                                                            [
                                                                                ...prev,
                                                                            ];
                                                                        next[
                                                                            qi
                                                                        ] = {
                                                                            ...next[
                                                                                qi
                                                                            ],
                                                                            answer: false,
                                                                        };
                                                                        return next;
                                                                    },
                                                                )
                                                            }
                                                        />
                                                        False
                                                    </label>
                                                </div>
                                            )}
                                        </CollapsibleContent>
                                    </Collapsible>
                                );
                            })
                        )}
                    </div>
                    {questions.length > 0 && (
                        <div className="rounded-lg border bg-muted/30 p-4 text-sm">
                            <p className="text-muted-foreground">
                                💡 Tip: Click the save icon on each question to
                                save it individually. Questions are saved
                                immediately to the database.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
