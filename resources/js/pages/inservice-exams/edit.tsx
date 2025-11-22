import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import { AssessmentLogsSheet } from '@/components/inservice-exams/assessment-logs-sheet';
import { QuestionSelectorDialog } from '@/components/question-bank/question-selector-dialog';
import { QuestionsImportPreviewDialog } from '@/components/questions-import-preview-dialog';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Check,
    ChevronDown,
    ChevronRight,
    Copy,
    Download,
    FileText,
    Save,
    Search,
    Trash2,
    Upload,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

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
    exam_category?: string | null;
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

export default function EditInServiceAssessment() {
    const { assessment } = usePage<PageProps>().props;

    const [title, setTitle] = useState(assessment.title);
    const [description, setDescription] = useState(
        assessment.description || '',
    );
    const [examCategory, setExamCategory] = useState(
        assessment.exam_category || '',
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
    const [topics, setTopics] = useState<
        Array<{ id: number; name: string; slug: string; is_global: boolean }>
    >([]);
    const [questionSearchQuery, setQuestionSearchQuery] = useState('');
    const [deletingQuestionIndex, setDeletingQuestionIndex] = useState<
        number | null
    >(null);
    const [duplicating, setDuplicating] = useState(false);

    // Import preview state
    const [showImportPreview, setShowImportPreview] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);
    const [previewData, setPreviewData] = useState<unknown>(null);
    const [isImporting, setIsImporting] = useState(false);
    const [showQuestionSelector, setShowQuestionSelector] = useState(false);
    const [viewingLogsAssessment, setViewingLogsAssessment] = useState<{
        id: number;
        title: string;
    } | null>(null);

    // Fetch topics once on page load
    useEffect(() => {
        fetch('/topics')
            .then((res) => res.json())
            .then((data) => setTopics(data))
            .catch((err) => console.error('Failed to fetch topics:', err));
    }, []);

    // Update questions when assessment changes (after import/reload)
    useEffect(() => {
        setQuestions(assessment.questions || []);
    }, [assessment.questions]);

    const toggleQuestion = (index: number) => {
        setOpenQuestions((prev) => ({ ...prev, [index]: !prev[index] }));
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
            const newQuestionElement = questionElements[newIndex] as
                | HTMLElement
                | undefined;
            if (newQuestionElement) {
                newQuestionElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
            }
        }, 100);
    };

    const handleDuplicateExam = () => {
        if (duplicating) return;
        setDuplicating(true);
        router.post(
            `/inservice-exams/${assessment.id}/duplicate`,
            {},
            {
                preserveScroll: true,
                onError: () => {
                    toast.error('Failed to duplicate exam.');
                },
                onFinish: () => {
                    setDuplicating(false);
                },
            },
        );
    };

    const handleImportFile = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setImportFile(file);
        const formData = new FormData();
        formData.append('file', file);
        try {
            const response = await fetch(
                `/inservice-exams/${assessment.id}/questions/preview`,
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN':
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || '',
                    },
                },
            );
            const data = await response.json();
            if (data.success) {
                setPreviewData(data);
                setShowImportPreview(true);
            } else {
                toast.error(data.message || 'Failed to preview questions');
            }
        } catch (error) {
            console.error('Preview error:', error);
            toast.error('Failed to preview questions');
        } finally {
            e.target.value = '';
        }
    };

    const handleConfirmImport = () => {
        if (!importFile) return;
        setIsImporting(true);
        const formData = new FormData();
        formData.append('file', importFile);
        router.post(
            `/inservice-exams/${assessment.id}/questions/import`,
            formData,
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    setShowImportPreview(false);
                    setImportFile(null);
                    setPreviewData(null);
                    router.reload({ only: ['assessment'] });
                },
                onError: (errors) => {
                    console.error('Import errors:', errors);
                    toast.error('Import failed');
                },
                onFinish: () => {
                    setIsImporting(false);
                },
            },
        );
    };

    const updateExam = () => {
        if (!title) {
            toast.error('Please enter a title');
            return;
        }
        router.patch(
            `/inservice-exams/${assessment.id}`,
            {
                title,
                description: description || null,
                category: examCategory || null,
                passing_score: passingScore,
                duration_minutes: duration || null,
                randomize_questions: randomizeQuestions,
                randomize_choices: randomizeChoices,
                show_results_immediately: showResultsImmediately,
                allow_review: allowReview,
                is_published: isPublished,
                // API expects scheduled/results fields for in-service
                scheduled_date: availableFrom || null,
                results_release_date: availableUntil || null,
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
            `/inservice-exams/${assessment.id}/questions/save-one`,
            question as never,
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page: {
                    props?: { flash?: { question?: { id: number } } };
                }) => {
                    const savedQuestion = page.props?.flash?.question;
                    if (savedQuestion) {
                        setQuestions((prev) => {
                            const next = [...prev];
                            next[qi] = { ...next[qi], id: savedQuestion.id };
                            return next;
                        });
                    }
                    setSavingQuestion(null);
                    toast.success('Question saved successfully!');
                },
                onError: (_errors) => {
                    console.error('Error saving question:', errors);
                    toast.error('Failed to save question');
                    setSavingQuestion(null);
                },
            },
        );
    };

    const deleteOneQuestion = (qi: number) => {
        setDeletingQuestionIndex(qi);
    };

    const confirmDeleteQuestion = () => {
        if (deletingQuestionIndex === null) return;
        const qi = deletingQuestionIndex;
        const question = questions[qi];
        if (question.id) {
            router.delete(
                `/inservice-exams/${assessment.id}/questions/${question.id}`,
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
                    onFinish: () => setDeletingQuestionIndex(null),
                },
            );
        } else {
            setQuestions((prev) => prev.filter((_, i) => i !== qi));
            toast.success('Question removed');
            setDeletingQuestionIndex(null);
        }
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

    // helper intentionally omitted (not used in this screen)

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
                { title: 'In-Service Exams', href: '/inservice-exams/active' },
                { title: 'Edit Exam', href: '#' },
            ]}
        >
            <Head title="Edit In-Service Exam" />

            <div className="space-y-8 p-6">
                {/* Exam Metadata */}
                <div className="space-y-4 rounded-lg border p-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h3 className="text-lg font-semibold">Exam Details</h3>
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setViewingLogsAssessment({
                                        id: assessment.id,
                                        title: assessment.title,
                                    })
                                }
                            >
                                <FileText className="mr-2 h-4 w-4" />
                                View Logs
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={handleDuplicateExam}
                                disabled={duplicating}
                            >
                                <Copy className="mr-2 h-4 w-4" />
                                {duplicating ? 'Duplicating...' : 'Duplicate Exam'}
                            </Button>
                        </div>
                    </div>
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
                    <div className="space-y-2">
                        <Label>Exam Category</Label>
                        <Select
                            value={examCategory || ''}
                            onValueChange={setExamCategory}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select category (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                {[
                                    'Long Quiz',
                                    'Short Quiz',
                                    'Practical Exam',
                                    'Laboratory Exam',
                                    'Midterm Exam',
                                    'Final Exam',
                                    'Preliminary Exam',
                                    'Diagnostic Exam',
                                    'Pre-test',
                                    'Post-test',
                                    'Mock Exam',
                                    'Other',
                                ].map((category) => (
                                    <SelectItem key={category} value={category}>
                                        {category}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
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
                    <div className="flex items-center justify-between">
                        <h3 className="text-lg font-semibold">Questions</h3>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setShowQuestionSelector(true)}
                            >
                                <Search className="mr-2 h-4 w-4" />
                                Add from Bank
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    window.open(
                                        '/inservice-exams/questions/template',
                                        '_blank',
                                    )
                                }
                            >
                                <Download className="mr-2 h-4 w-4" />
                                Download Template
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    document
                                        .getElementById('import-file')
                                        ?.click()
                                }
                            >
                                <Upload className="mr-2 h-4 w-4" />
                                Import from Excel
                            </Button>
                            <input
                                id="import-file"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                className="hidden"
                                onChange={handleImportFile}
                            />
                        </div>
                    </div>
                    <div className="flex items-center justify-between gap-2">
                        <div className="flex flex-wrap gap-2">
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
                                        className={`rounded border transition-colors ${
                                            openQuestions[qi]
                                                ? 'border-primary bg-primary/5 shadow-sm'
                                                : ''
                                        }`}
                                    >
                                        <div
                                            className={`flex items-center justify-between border-b p-3 transition-colors ${
                                                openQuestions[qi]
                                                    ? 'bg-primary/10 border-primary/20'
                                                    : 'bg-muted/50'
                                            }`}
                                        >
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
                                                                if (file)
                                                                    handleImageUpload(
                                                                        qi,
                                                                        file,
                                                                    );
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
                                                                        className={`min-w-[80px] text-sm font-medium ${
                                                                            ci ===
                                                                            0
                                                                                ? 'text-green-600 dark:text-green-400'
                                                                                : 'text-muted-foreground'
                                                                        }`}
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

            <DeleteConfirmationDialog
                open={deletingQuestionIndex !== null}
                title="Delete Question?"
                itemIdentifier={
                    deletingQuestionIndex !== null
                        ? `Question #${deletingQuestionIndex + 1}`
                        : undefined
                }
                itemName={
                    deletingQuestionIndex !== null
                        ? questions[
                              deletingQuestionIndex
                          ]?.question_text?.replace(/<[^>]*>/g, '')
                        : undefined
                }
                warningMessage="This action cannot be undone. This will permanently delete the question and all its associated choices."
                confirmText="Delete Question"
                onConfirm={confirmDeleteQuestion}
                onCancel={() => setDeletingQuestionIndex(null)}
            />

            {previewData && (
                <QuestionsImportPreviewDialog
                    open={showImportPreview}
                    questions={previewData.questions || []}
                    errors={previewData.errors || []}
                    totalValid={previewData.total_valid || 0}
                    totalErrors={previewData.total_errors || 0}
                    onConfirm={handleConfirmImport}
                    onCancel={() => {
                        setShowImportPreview(false);
                        setImportFile(null);
                        setPreviewData(null);
                    }}
                    isImporting={isImporting}
                />
            )}

            <QuestionSelectorDialog
                open={showQuestionSelector}
                onOpenChange={setShowQuestionSelector}
                assessmentId={assessment.id}
                onQuestionsAdded={() => router.reload({ only: ['assessment'] })}
            />

            {/* Assessment Logs Sheet */}
            <AssessmentLogsSheet
                assessment={viewingLogsAssessment}
                open={!!viewingLogsAssessment}
                onOpenChange={(open) => {
                    if (!open) {
                        setViewingLogsAssessment(null);
                    }
                }}
            />
        </AppLayout>
    );
}
