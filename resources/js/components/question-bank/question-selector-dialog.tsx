import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { router } from '@inertiajs/react';
import { Check, Clock, Search, TrendingUp } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Question {
    id: number;
    question_text: string;
    question_type: string;
    points: number;
    difficulty_level: string | null;
    is_approved: boolean;
    topic: { id: number; name: string } | null;
    statistics: {
        times_answered: number;
        success_rate: number;
        average_time_seconds: number | null;
        computed_difficulty: string | null;
    } | null;
}

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    assessmentId: number;
    onQuestionsAdded: () => void;
    routePrefix?: string; // Optional route prefix (e.g., 'inservice-exams' or 'assessments')
    scope?: 'institution' | 'national';
    existingQuestions?: Array<{
        question_text: string;
        question_type: string;
    }>;
}

interface TopicOption {
    id: number;
    name: string;
}

const typeLabels: Record<string, string> = {
    multiple_choice: 'Multiple Choice',
    multiple_select: 'Multiple Select',
    true_false: 'True/False',
};

const difficultyColors: Record<string, string> = {
    easy: 'border-transparent bg-success-soft text-success',
    medium: 'border-transparent bg-warning-soft text-warning',
    hard: 'border-transparent bg-destructive/12 text-destructive',
};

export function QuestionSelectorDialog({
    open,
    onOpenChange,
    assessmentId,
    onQuestionsAdded,
    routePrefix,
    scope = 'institution',
    existingQuestions = [],
}: Props) {
    const [questions, setQuestions] = useState<Question[]>([]);
    const [topics, setTopics] = useState<TopicOption[]>([]);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [filters, setFilters] = useState({
        search: '',
        topic: '',
        type: '',
        approval: '',
    });

    useEffect(() => {
        if (open) {
            loadTopics();
            loadQuestions();
        }
    }, [open, filters, scope]);

    const loadTopics = async () => {
        try {
            const response = await fetch('/topics', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to load topics.');
            }

            const data = await response.json();
            setTopics(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error loading topics:', error);
        }
    };

    const loadQuestions = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (filters.search) params.append('search', filters.search);
            if (filters.topic) params.append('topic', filters.topic);
            if (filters.type) params.append('type', filters.type);
            if (filters.approval) params.append('approval', filters.approval);
            params.append('scope', scope);

            const response = await fetch(
                `/question-bank/list?${params.toString()}`,
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                },
            );

            if (!response.ok) {
                throw new Error('Failed to load question bank.');
            }

            const data = await response.json();
            setQuestions(data.data || []);
        } catch (error) {
            console.error('Error loading questions:', error);
        } finally {
            setLoading(false);
        }
    };

    const toggleQuestion = (questionId: number) => {
        setSelectedIds((prev) =>
            prev.includes(questionId)
                ? prev.filter((id) => id !== questionId)
                : [...prev, questionId],
        );
    };

    const buildQuestionSignature = (questionText: string, questionType: string) =>
        `${questionType}|${questionText
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase()}`;

    const existingQuestionSignatures = new Set(
        existingQuestions.map((question) =>
            buildQuestionSignature(
                question.question_text,
                question.question_type,
            ),
        ),
    );

    const selectableQuestionIds = new Set(
        questions
            .filter(
                (question) =>
                    !existingQuestionSignatures.has(
                        buildQuestionSignature(
                            question.question_text,
                            question.question_type,
                        ),
                    ),
            )
            .map((question) => question.id),
    );

    const effectiveSelectedIds = selectedIds.filter((id) =>
        selectableQuestionIds.has(id),
    );

    useEffect(() => {
        if (effectiveSelectedIds.length !== selectedIds.length) {
            setSelectedIds(effectiveSelectedIds);
        }
    }, [effectiveSelectedIds, selectedIds]);

    const handleAddQuestions = () => {
        if (effectiveSelectedIds.length === 0 || submitting) {
            return;
        }

        const prefix =
            routePrefix ||
            (window.location.pathname.includes('/inservice-exams/')
                ? 'inservice-exams'
                : 'assessments');
        const route = `/${prefix}/${assessmentId}/questions/from-bank`;

        setSubmitting(true);

        router.post(
            route,
            { question_ids: effectiveSelectedIds },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedIds([]);
                    onQuestionsAdded();
                    onOpenChange(false);
                },
                onFinish: () => {
                    setSubmitting(false);
                },
            },
        );
    };

    const getDifficulty = (question: Question) => {
        return (
            question.statistics?.computed_difficulty ||
            question.difficulty_level ||
            'medium'
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[92vh] w-[95vw] border border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_98%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))] shadow-[0_26px_64px_-36px_rgb(0_0_0_/_0.56)] sm:!max-w-3xl lg:!max-w-5xl">
                <DialogHeader>
                    <DialogTitle>Select Questions from Bank</DialogTitle>
                    <DialogDescription>
                        Browse and select questions from your question bank to
                        add to this exam.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-col gap-4">
                    {/* Filters */}
                    <div className="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(320px,1.6fr)_minmax(220px,1fr)_minmax(180px,0.8fr)_minmax(180px,0.8fr)]">
                        <div className="relative flex-1">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Search questions..."
                                value={filters.search}
                                onChange={(e) =>
                                    setFilters({
                                        ...filters,
                                        search: e.target.value,
                                    })
                                }
                                className="h-11 w-full rounded-2xl border-border/70 bg-background/88 pl-9"
                            />
                        </div>
                        <Select
                            value={filters.topic || 'all'}
                            onValueChange={(value) =>
                                setFilters({
                                    ...filters,
                                    topic: value === 'all' ? '' : value,
                                })
                            }
                        >
                            <SelectTrigger className="h-11 w-full rounded-2xl border-border/70 bg-background/88">
                                <SelectValue placeholder="All Topics / Categories" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All Topics / Categories
                                </SelectItem>
                                {topics.map((topic) => (
                                    <SelectItem
                                        key={topic.id}
                                        value={String(topic.id)}
                                    >
                                        {topic.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={filters.type || 'all'}
                            onValueChange={(value) =>
                                setFilters({
                                    ...filters,
                                    type: value === 'all' ? '' : value,
                                })
                            }
                        >
                            <SelectTrigger className="h-11 w-full rounded-2xl border-border/70 bg-background/88">
                                <SelectValue placeholder="All Types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Types</SelectItem>
                                <SelectItem value="multiple_choice">
                                    Multiple Choice
                                </SelectItem>
                                <SelectItem value="multiple_select">
                                    Multiple Select
                                </SelectItem>
                                <SelectItem value="true_false">
                                    True/False
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Select
                            value={filters.approval || 'all'}
                            onValueChange={(value) =>
                                setFilters({
                                    ...filters,
                                    approval: value === 'all' ? '' : value,
                                })
                            }
                        >
                            <SelectTrigger className="h-11 w-full rounded-2xl border-border/70 bg-background/88">
                                <SelectValue placeholder="All Approval States" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All Approval States
                                </SelectItem>
                                <SelectItem value="approved">
                                    Approved
                                </SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Questions List */}
                    <div className="max-h-[58vh] min-h-[420px] space-y-3 overflow-y-auto rounded-[1.4rem] border border-border/80 bg-background/36 p-4">
                        {loading ? (
                            <div className="py-8 text-center text-muted-foreground">
                                Loading questions...
                            </div>
                        ) : questions.length === 0 ? (
                            <div className="py-8 text-center text-muted-foreground">
                                No questions found. Try adjusting your filters
                                or create new questions.
                            </div>
                        ) : (
                            questions.map((question) => {
                                const difficulty = getDifficulty(question);
                                const stats = question.statistics;
                                const alreadyInExam =
                                    existingQuestionSignatures.has(
                                        buildQuestionSignature(
                                            question.question_text,
                                            question.question_type,
                                        ),
                                    );
                                const isSelected =
                                    !alreadyInExam &&
                                    effectiveSelectedIds.includes(question.id);

                                return (
                                    <div
                                        key={question.id}
                                        className={`flex items-start gap-3 rounded-[1.2rem] border border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))] p-4 shadow-[0_16px_30px_-28px_rgb(0_0_0_/_0.42)] transition-[border-color,box-shadow,transform,background-color] ${
                                            alreadyInExam
                                                ? 'cursor-not-allowed opacity-60'
                                                : 'cursor-pointer hover:-translate-y-0.5 hover:border-primary/15 hover:shadow-[0_22px_40px_-30px_rgb(96_44_193_/_0.2)]'
                                        } ${
                                            isSelected
                                                ? 'border-primary/35 bg-accent/40 shadow-[0_22px_40px_-30px_rgb(96_44_193_/_0.24)]'
                                                : ''
                                        }`}
                                        onClick={() => {
                                            if (alreadyInExam) {
                                                return;
                                            }

                                            toggleQuestion(question.id);
                                        }}
                                    >
                                        <Checkbox
                                            checked={isSelected}
                                            className="mt-1"
                                            disabled={alreadyInExam}
                                        />
                                        <div className="flex-1 space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                {alreadyInExam && (
                                                    <Badge className="border-transparent bg-muted text-muted-foreground">
                                                        Already in exam
                                                    </Badge>
                                                )}
                                                {question.is_approved && (
                                                    <Badge
                                                        className="border-transparent bg-success-soft text-success"
                                                    >
                                                        <Check className="mr-1 h-3 w-3" />
                                                        Approved
                                                    </Badge>
                                                )}
                                                <Badge className="border-border/70 bg-background/88 text-foreground">
                                                    {
                                                        typeLabels[
                                                            question
                                                                .question_type
                                                        ]
                                                    }
                                                </Badge>
                                                <Badge
                                                    className={
                                                        difficultyColors[
                                                            difficulty
                                                        ]
                                                    }
                                                >
                                                    {difficulty
                                                        .charAt(0)
                                                        .toUpperCase() +
                                                        difficulty.slice(1)}
                                                </Badge>
                                                {question.topic && (
                                                    <Badge className="border-transparent bg-muted text-muted-foreground">
                                                        {question.topic.name}
                                                    </Badge>
                                                )}
                                                <Badge className="border-border/70 bg-background/88 text-foreground">
                                                    {question.points} pts
                                                </Badge>
                                            </div>

                                            <div
                                                className="line-clamp-2 text-sm"
                                                dangerouslySetInnerHTML={{
                                                    __html: question.question_text,
                                                }}
                                            />

                                            {stats &&
                                                stats.times_answered > 0 && (
                                                    <div className="flex flex-wrap gap-4 text-xs text-muted-foreground">
                                                        <div className="flex items-center gap-1">
                                                            <TrendingUp className="h-3 w-3" />
                                                            <span>
                                                                Success:{' '}
                                                                {typeof stats.success_rate ===
                                                                'number'
                                                                    ? stats.success_rate.toFixed(
                                                                          0,
                                                                      )
                                                                    : Number(
                                                                          stats.success_rate ||
                                                                              0,
                                                                      ).toFixed(
                                                                          0,
                                                                      )}
                                                                %
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center gap-1">
                                                            <Check className="h-3 w-3" />
                                                            <span>
                                                                {
                                                                    stats.times_answered
                                                                }{' '}
                                                                attempts
                                                            </span>
                                                        </div>
                                                        {stats.average_time_seconds && (
                                                            <div className="flex items-center gap-1">
                                                                <Clock className="h-3 w-3" />
                                                                <span>
                                                                    Avg:{' '}
                                                                    {Math.round(
                                                                        stats.average_time_seconds,
                                                                    )}
                                                                    s
                                                                </span>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-between border-t pt-4">
                        <p className="text-sm text-muted-foreground">
                            {effectiveSelectedIds.length} question(s) selected
                        </p>
                        <div className="flex gap-3">
                            <Button
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                                disabled={submitting}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={handleAddQuestions}
                                disabled={
                                    effectiveSelectedIds.length === 0 ||
                                    submitting
                                }
                            >
                                {submitting
                                    ? 'Adding...'
                                    : `Add ${effectiveSelectedIds.length > 0 ? `${effectiveSelectedIds.length} ` : ''}Questions`}
                            </Button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
