import { RichTextEditor } from '@/components/rich-text-editor';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import { Check, ChevronDown, ChevronRight, ChevronsUpDown, Plus, Trash2, Upload, X } from 'lucide-react';
import { useEffect, useState } from 'react';

interface QuestionChoice {
    choice_text: string;
    is_correct: boolean;
}

interface DraftQuestion {
    question_type: 'multiple_choice' | 'multiple_select' | 'true_false';
    topic_id?: number | null;
    question_text: string;
    points: number;
    choices?: QuestionChoice[];
    answer?: boolean;
    order?: number;
    image?: string; // base64 encoded image
    image_url?: string; // for preview
}

interface Topic {
    id: number;
    name: string;
    slug: string;
    is_global: boolean;
}

interface PageProps {
    assessmentId?: number;
}

export default function CreateAssessment({ assessmentId: propAssessmentId }: PageProps) {
    const [title, setTitle] = useState('');
    const [passingScore, setPassingScore] = useState(0);
    const [duration, setDuration] = useState<number | ''>('');
    const [questions, setQuestions] = useState<DraftQuestion[]>([]);
    const [openQuestions, setOpenQuestions] = useState<Record<number, boolean>>({});
    const [topics, setTopics] = useState<Topic[]>([]);
    const [isAddingTopic, setIsAddingTopic] = useState<number | false>(false);
    const [newTopicName, setNewTopicName] = useState('');
    const [openTopicCombobox, setOpenTopicCombobox] = useState<number | false>(false);
    const [topicSearch, setTopicSearch] = useState('');

    // Get assessment ID from props (passed from backend)
    const assessmentId = propAssessmentId || null;

    // Fetch topics
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
            };
            return next;
        });
    };

    const createNewTopic = () => {
        if (!newTopicName.trim()) {
            alert('Please enter a topic name');
            return;
        }

        const questionIndex = isAddingTopic;
        if (questionIndex === false) return;

        router.post(
            '/topics',
            { name: newTopicName },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    // Refetch topics to get the newly created one
                    fetch('/topics')
                        .then((res) => res.json())
                        .then((data) => {
                            setTopics(data);
                            // Find the newly created topic (last one in the list)
                            const newTopic = data.find((t: Topic) => t.name === newTopicName);
                            if (newTopic) {
                                // Auto-select the new topic for the current question
                                setQuestions((prev) => {
                                    const next = [...prev];
                                    next[questionIndex] = {
                                        ...next[questionIndex],
                                        topic_id: newTopic.id,
                                    };
                                    return next;
                                });
                            }
                            setNewTopicName('');
                            setIsAddingTopic(false);
                        })
                        .catch((err) => {
                            console.error('Failed to refetch topics:', err);
                            setNewTopicName('');
                            setIsAddingTopic(false);
                        });
                },
                onError: (errors) => {
                    console.error('Error creating topic:', errors);
                    alert('Failed to create topic');
                },
            }
        );
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
                    alert('Failed to save questions. Check console for details.');
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
                    <h3 className="text-lg font-semibold">Step 1: Exam Details</h3>
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
                                    setPassingScore(parseInt(e.target.value || '0'))
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
                                    const allOpen = Object.values(openQuestions).every(Boolean);
                                    const newState: Record<number, boolean> = {};
                                    questions.forEach((_, i) => {
                                        newState[i] = !allOpen;
                                    });
                                    setOpenQuestions(newState);
                                }}
                            >
                                {Object.values(openQuestions).every(Boolean) ? 'Collapse All' : 'Expand All'}
                            </Button>
                        )}
                    </div>

                    <div className="space-y-6">
                        {questions.map((q, qi) => (
                            <Collapsible
                                key={qi}
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
                                            {q.question_type.replace('_', ' ')})
                                            {q.question_text && (
                                                <span className="ml-2 font-normal text-muted-foreground">
                                                    - {q.question_text.substring(0, 50).replace(/<[^>]*>/g, '')}
                                                    {q.question_text.length > 50 ? '...' : ''}
                                                </span>
                                            )}
                                        </Label>
                                    </CollapsibleTrigger>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => {
                                            setQuestions((prev) =>
                                                prev.filter((_, i) => i !== qi),
                                            );
                                        }}
                                    >
                                        <Trash2 className="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                                <CollapsibleContent className="p-4">
                                <div className="grid gap-4">
                                    {/* Topic Selection */}
                                    <div className="space-y-2">
                                        <Label>Topic (Optional)</Label>
                                        <div className="flex gap-2">
                                            <Popover open={openTopicCombobox === qi} onOpenChange={(open) => setOpenTopicCombobox(open ? qi : false)}>
                                                <PopoverTrigger asChild>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        role="combobox"
                                                        aria-expanded={openTopicCombobox === qi}
                                                        className="flex-1 justify-between"
                                                    >
                                                        {q.topic_id
                                                            ? topics.find((topic) => topic.id === q.topic_id)?.name
                                                            : "Select topic..."}
                                                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent className="w-[400px] p-0" align="start">
                                                    <Command>
                                                        <CommandInput 
                                                            placeholder="Search topics..." 
                                                            value={topicSearch}
                                                            onValueChange={setTopicSearch}
                                                        />
                                                        <CommandList>
                                                            <CommandGroup>
                                                                {!topicSearch && (
                                                                    <div
                                                                        className="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
                                                                        onClick={() => {
                                                                            setQuestions((prev) => {
                                                                                const next = [...prev];
                                                                                next[qi] = {
                                                                                    ...next[qi],
                                                                                    topic_id: null,
                                                                                };
                                                                                return next;
                                                                            });
                                                                            setOpenTopicCombobox(false);
                                                                            setTopicSearch('');
                                                                        }}
                                                                    >
                                                                        <Check
                                                                            className={cn(
                                                                                "mr-2 h-4 w-4",
                                                                                !q.topic_id ? "opacity-100" : "opacity-0"
                                                                            )}
                                                                        />
                                                                        No topic
                                                                    </div>
                                                                )}
                                                                {topics
                                                                    .filter((topic) =>
                                                                        topic.name.toLowerCase().includes(topicSearch.toLowerCase())
                                                                    )
                                                                    .map((topic) => (
                                                                        <div
                                                                            key={topic.id}
                                                                            className={cn(
                                                                                "relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground",
                                                                                q.topic_id === topic.id && "bg-accent"
                                                                            )}
                                                                            onClick={() => {
                                                                                setQuestions((prev) => {
                                                                                    const next = [...prev];
                                                                                    next[qi] = {
                                                                                        ...next[qi],
                                                                                        topic_id: topic.id,
                                                                                    };
                                                                                    return next;
                                                                                });
                                                                                setOpenTopicCombobox(false);
                                                                                setTopicSearch('');
                                                                            }}
                                                                        >
                                                                            <Check
                                                                                className={cn(
                                                                                    "mr-2 h-4 w-4",
                                                                                    q.topic_id === topic.id ? "opacity-100" : "opacity-0"
                                                                                )}
                                                                            />
                                                                            {topic.name} {topic.is_global && <span className="text-muted-foreground">(Global)</span>}
                                                                        </div>
                                                                    ))}
                                                                {topicSearch && topics.filter((topic) =>
                                                                    topic.name.toLowerCase().includes(topicSearch.toLowerCase())
                                                                ).length === 0 && (
                                                                    <div className="py-6 text-center text-sm text-muted-foreground">
                                                                        No topic found.
                                                                    </div>
                                                                )}
                                                            </CommandGroup>
                                                        </CommandList>
                                                    </Command>
                                                </PopoverContent>
                                            </Popover>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setIsAddingTopic(qi)}
                                            >
                                                <Plus className="h-4 w-4" />
                                            </Button>
                                        </div>
                                        {isAddingTopic === qi && (
                                            <div className="flex gap-2 rounded border bg-muted/30 p-3">
                                                <Input
                                                    placeholder="New topic name"
                                                    value={newTopicName}
                                                    onChange={(e) => setNewTopicName(e.target.value)}
                                                    onKeyDown={(e) => {
                                                        if (e.key === 'Enter') {
                                                            e.preventDefault();
                                                            createNewTopic();
                                                        }
                                                    }}
                                                />
                                                <Button type="button" size="sm" onClick={createNewTopic}>
                                                    Add
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => {
                                                        setIsAddingTopic(false);
                                                        setNewTopicName('');
                                                    }}
                                                >
                                                    Cancel
                                                </Button>
                                            </div>
                                        )}
                                    </div>

                                    {/* Question Text */}
                                    <div className="grid gap-2">
                                        <Label>Question Text</Label>
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
                                </div>
                                <div className="mt-3 space-y-3">
                                    <Label>Question Image (Optional)</Label>
                                    {q.image_url ? (
                                        <div className="relative flex">
                                            <img
                                                src={q.image_url}
                                                alt="Question"
                                                className="h-auto max-h-96 w-full max-w-2xl rounded border object-contain"
                                            />
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                className="absolute right-2 top-2"
                                                onClick={() => removeImage(qi)}
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
                                                        e.target.files?.[0];
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
                                    q.question_type === 'multiple_select') && (
                                    <div className="mt-4 space-y-2">
                                        <Label>Choices (Choice #1 is the correct answer)</Label>
                                        {(q.choices || []).map((c, ci) => (
                                            <div
                                                key={ci}
                                                className="flex items-center gap-2"
                                            >
                                                <div className="flex w-full items-center gap-2">
                                                    <span className={`min-w-[80px] text-sm font-medium ${ci === 0 ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'}`}>
                                                        Choice #{ci + 1}{ci === 0 ? ' ✓' : ''}
                                                    </span>
                                                    <Input
                                                        value={c.choice_text}
                                                        onChange={(e) => {
                                                            setQuestions((prev) => {
                                                                const next = [...prev];
                                                                const choices = [...(next[qi].choices || [])];
                                                                choices[ci] = {
                                                                    ...choices[ci],
                                                                    choice_text: e.target.value,
                                                                    is_correct: ci === 0,
                                                                };
                                                                next[qi] = { ...next[qi], choices };
                                                                return next;
                                                            });
                                                        }}
                                                        placeholder={`Enter choice ${ci + 1}`}
                                                        className={ci === 0 ? 'border-green-500' : ''}
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                        {(q.choices?.length || 0) < 4 && (
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="ghost"
                                                    onClick={() =>
                                                        setQuestions((prev) => {
                                                            const next = [...prev];
                                                            const choices = [
                                                                ...(next[qi]
                                                                    .choices || []),
                                                            ];
                                                            if (choices.length < 4) {
                                                                choices.push({
                                                                    choice_text: '',
                                                                    is_correct: false,
                                                                });
                                                            }
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
                                        )}
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
                                                        const next = [...prev];
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
                                </CollapsibleContent>
                            </Collapsible>
                        ))}
                    </div>
                    <div className="flex gap-3">
                        <Button onClick={saveQuestions}>Save Questions</Button>
                    </div>
                </div>
                )}
            </div>
        </AppLayout>
    );
}
