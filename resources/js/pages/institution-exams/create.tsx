import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Topic {
    id: number;
    name: string;
    slug: string;
    is_global: boolean;
}

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

interface PageProps {
    assessmentId?: number;
}

// Define exam categories
const EXAM_CATEGORIES = [
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
] as const;

export default function CreateAssessment({
    assessmentId: propAssessmentId,
}: PageProps) {
    const csrfToken =
        typeof document !== 'undefined'
            ? (document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute('content') ?? '')
            : '';
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [examCategory, setExamCategory] = useState('');
    const [passingScore, setPassingScore] = useState<number | ''>('');
    const [duration, setDuration] = useState<number | ''>('');
    const [randomizeQuestions, setRandomizeQuestions] = useState(false);
    const [randomizeChoices, setRandomizeChoices] = useState(false);
    const [showResultsImmediately, setShowResultsImmediately] = useState(true);
    const [allowReview, setAllowReview] = useState(true);
    const [isPublished, setIsPublished] = useState(false);
    const [availableFrom, setAvailableFrom] = useState('');
    const [availableUntil, setAvailableUntil] = useState('');
    const [questions, setQuestions] = useState<DraftQuestion[]>([]);
    const [openQuestions, setOpenQuestions] = useState<Record<number, boolean>>(
        {},
    );
    const [savingQuestion, setSavingQuestion] = useState<number | null>(null);
    const [topics, setTopics] = useState<Topic[]>([]);
    const [questionSearchQuery, setQuestionSearchQuery] = useState('');
    const [deletingQuestionIndex, setDeletingQuestionIndex] = useState<
        number | null
    >(null);

    // Get assessment ID from props (passed from backend)
    const assessmentId = propAssessmentId || null;

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

    const createExam = () => {
        if (!title) {
            toast.error('Please enter a title');
            return;
        }

        // Step 1: Create exam metadata
        router.post(
            '/assessments',
            {
                _token: csrfToken,
                title,
                description: description || null,
                exam_category: examCategory || null,
                passing_score: passingScore === '' ? 0 : passingScore,
                duration_minutes: duration === '' ? null : duration,
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
                    toast.success('Exam created! Now add questions.');
                },
                onError: (errors) => {
                    console.error('Error creating exam:', errors);
                    toast.error('Failed to create exam');
                },
            },
        );
    };

    const saveOneQuestion = (qi: number) => {
        if (!assessmentId) {
            toast.error('Please create the exam first');
            return;
        }

        const question = questions[qi];
        if (!question.question_text) {
            toast.error('Please enter question text');
            return;
        }

        setSavingQuestion(qi);

        router.post(
            `/assessments/${assessmentId}/questions/save-one`,
            {
                _token: csrfToken,
                ...question,
            } as any,
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
        setDeletingQuestionIndex(qi);
    };

    const confirmDeleteQuestion = () => {
        if (deletingQuestionIndex === null) return;

        const qi = deletingQuestionIndex;
        const question = questions[qi];

        if (question.id) {
            // Delete from database
            router.delete(
                `/assessments/${assessmentId}/questions/${question.id}`,
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
            // Just remove from local state (not saved yet)
            setQuestions((prev) => prev.filter((_, i) => i !== qi));
            toast.success('Question removed');
            setDeletingQuestionIndex(null);
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
                    <div className="space-y-2">
                        <Label>Description</Label>
                        <Textarea
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="Describe the exam (optional)"
                            disabled={!!assessmentId}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Exam Category</Label>
                        <Select
                            value={examCategory}
                            onValueChange={setExamCategory}
                            disabled={!!assessmentId}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select category (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                {EXAM_CATEGORIES.map((category) => (
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
                                onChange={(e) => {
                                    const value = e.target.value;
                                    setPassingScore(
                                        value === '' ? '' : parseInt(value, 10),
                                    );
                                }}
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
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Available From</Label>
                            <Input
                                type="datetime-local"
                                value={availableFrom}
                                onChange={(e) =>
                                    setAvailableFrom(e.target.value)
                                }
                                disabled={!!assessmentId}
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
                                disabled={!!assessmentId}
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
                                disabled={!!assessmentId}
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
                                disabled={!!assessmentId}
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
                                disabled={!!assessmentId}
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
                                disabled={!!assessmentId}
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
                                disabled={!!assessmentId}
                            />
                            <Label htmlFor="is-published">
                                Publish exam (make it visible to residents)
                            </Label>
                        </div>
                    </div>
                    {!assessmentId && (
                        <Button onClick={createExam}>Create Exam</Button>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
