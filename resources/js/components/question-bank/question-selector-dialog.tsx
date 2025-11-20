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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { router } from '@inertiajs/react';
import { Check, Clock, Search, TrendingUp } from 'lucide-react';
import { useState, useEffect } from 'react';

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
}

const typeLabels: Record<string, string> = {
    multiple_choice: 'Multiple Choice',
    multiple_select: 'Multiple Select',
    true_false: 'True/False',
};

const difficultyColors: Record<string, string> = {
    easy: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    hard: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

export function QuestionSelectorDialog({ open, onOpenChange, assessmentId, onQuestionsAdded }: Props) {
    const [questions, setQuestions] = useState<Question[]>([]);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [loading, setLoading] = useState(false);
    const [filters, setFilters] = useState({
        search: '',
        type: '',
        approval: 'approved',
    });

    useEffect(() => {
        if (open) {
            loadQuestions();
        }
    }, [open, filters]);

    const loadQuestions = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (filters.search) params.append('search', filters.search);
            if (filters.type) params.append('type', filters.type);
            if (filters.approval) params.append('approval', filters.approval);
            
            // The backend will automatically determine if we're in national context
            // based on the current organization type, so we don't need to pass org param
            
            const response = await fetch(`/question-bank/list?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

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
                : [...prev, questionId]
        );
    };

    const handleAddQuestions = () => {
        router.post(
            `/assessments/${assessmentId}/questions/from-bank`,
            { question_ids: selectedIds },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedIds([]);
                    onQuestionsAdded();
                    onOpenChange(false);
                },
            }
        );
    };

    const getDifficulty = (question: Question) => {
        return question.statistics?.computed_difficulty || question.difficulty_level || 'medium';
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-4xl overflow-hidden">
                <DialogHeader>
                    <DialogTitle>Select Questions from Bank</DialogTitle>
                    <DialogDescription>
                        Browse and select questions from your question bank to add to this exam.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-col gap-4">
                    {/* Filters */}
                    <div className="flex flex-col gap-3 sm:flex-row">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Search questions..."
                                value={filters.search}
                                onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                                className="pl-9"
                            />
                        </div>
                        <Select value={filters.type} onValueChange={(value) => setFilters({ ...filters, type: value })}>
                            <SelectTrigger className="w-full sm:w-[180px]">
                                <SelectValue placeholder="All Types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="multiple_choice">Multiple Choice</SelectItem>
                                <SelectItem value="multiple_select">Multiple Select</SelectItem>
                                <SelectItem value="true_false">True/False</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Questions List */}
                    <div className="max-h-[50vh] space-y-3 overflow-y-auto rounded-lg border p-4">
                        {loading ? (
                            <div className="py-8 text-center text-muted-foreground">Loading questions...</div>
                        ) : questions.length === 0 ? (
                            <div className="py-8 text-center text-muted-foreground">
                                No questions found. Try adjusting your filters or create new questions.
                            </div>
                        ) : (
                            questions.map((question) => {
                                const difficulty = getDifficulty(question);
                                const stats = question.statistics;
                                const isSelected = selectedIds.includes(question.id);

                                return (
                                    <div
                                        key={question.id}
                                        className={`flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors hover:bg-accent ${
                                            isSelected ? 'border-primary bg-accent' : ''
                                        }`}
                                        onClick={() => toggleQuestion(question.id)}
                                    >
                                        <Checkbox checked={isSelected} className="mt-1" />
                                        <div className="flex-1 space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                {question.is_approved && (
                                                    <Badge variant="default" className="bg-green-600">
                                                        <Check className="mr-1 h-3 w-3" />
                                                        Approved
                                                    </Badge>
                                                )}
                                                <Badge variant="outline">
                                                    {typeLabels[question.question_type]}
                                                </Badge>
                                                <Badge className={difficultyColors[difficulty]}>
                                                    {difficulty.charAt(0).toUpperCase() + difficulty.slice(1)}
                                                </Badge>
                                                {question.topic && (
                                                    <Badge variant="secondary">{question.topic.name}</Badge>
                                                )}
                                                <Badge variant="outline">{question.points} pts</Badge>
                                            </div>

                                            <div
                                                className="line-clamp-2 text-sm"
                                                dangerouslySetInnerHTML={{ __html: question.question_text }}
                                            />

                                            {stats && stats.times_answered > 0 && (
                                                <div className="flex flex-wrap gap-4 text-xs text-muted-foreground">
                                                    <div className="flex items-center gap-1">
                                                        <TrendingUp className="h-3 w-3" />
                                                        <span>Success: {typeof stats.success_rate === 'number' ? stats.success_rate.toFixed(0) : Number(stats.success_rate || 0).toFixed(0)}%</span>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <Check className="h-3 w-3" />
                                                        <span>{stats.times_answered} attempts</span>
                                                    </div>
                                                    {stats.average_time_seconds && (
                                                        <div className="flex items-center gap-1">
                                                            <Clock className="h-3 w-3" />
                                                            <span>Avg: {Math.round(stats.average_time_seconds)}s</span>
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
                            {selectedIds.length} question(s) selected
                        </p>
                        <div className="flex gap-3">
                            <Button variant="outline" onClick={() => onOpenChange(false)}>
                                Cancel
                            </Button>
                            <Button onClick={handleAddQuestions} disabled={selectedIds.length === 0}>
                                Add {selectedIds.length > 0 && `${selectedIds.length} `}Questions
                            </Button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

