import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import AnalyticsLayout from '@/layouts/analytics/analytics-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    CheckCircle2,
    Clock,
    FileQuestion,
    Search,
    TrendingDown,
    TrendingUp,
    X,
} from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Analytics',
        href: '/analytics/question-bank',
    },
];

interface Topic {
    id: number;
    name: string;
}

interface Statistics {
    times_used_in_exams: number;
    times_answered: number;
    times_correct: number;
    times_incorrect: number;
    success_rate: number;
    average_time_seconds: number | null;
    computed_difficulty: string | null;
    discrimination_index: number | null;
    skip_count: number;
    last_used_at: string | null;
}

interface Question {
    id: number;
    question_text: string;
    question_type: string;
    points: number;
    difficulty_level: string | null;
    is_approved: boolean;
    times_used: number;
    topic: Topic | null;
    creator: { id: number; name: string } | null;
    statistics: Statistics | null;
    assessments_count: number;
}

interface PaginatedQuestions {
    data: Question[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface Summary {
    total_questions: number;
    approved_questions: number;
    pending_questions: number;
    average_success_rate: number;
}

interface PageProps {
    questions: PaginatedQuestions;
    summary: Summary;
    topics: Topic[];
    filters: {
        search?: string;
        topic?: number;
        question_type?: string;
        difficulty?: string;
        approval_status?: string;
        performance_filter?: string;
        sort_by?: string;
        sort_order?: string;
    };
    [key: string]: unknown;
}

export default function QuestionBank() {
    const { questions, summary, topics, filters } =
        usePage<PageProps>().props;

    const [search, setSearch] = useState(filters.search || '');
    const [topicFilter, setTopicFilter] = useState(
        filters.topic?.toString() || 'all',
    );
    const [questionTypeFilter, setQuestionTypeFilter] = useState(
        filters.question_type || 'all',
    );
    const [difficultyFilter, setDifficultyFilter] = useState(
        filters.difficulty || 'all',
    );
    const [approvalFilter, setApprovalFilter] = useState(
        filters.approval_status || 'all',
    );
    const [performanceFilter, setPerformanceFilter] = useState(
        filters.performance_filter || 'all',
    );
    const [sortBy, setSortBy] = useState(filters.sort_by || 'created_at');
    const [sortOrder, setSortOrder] = useState(filters.sort_order || 'desc');

    const handleSearch = () => {
        router.get(
            '/analytics/question-bank',
            {
                search: search || undefined,
                topic: topicFilter && topicFilter !== 'all' ? topicFilter : undefined,
                question_type: questionTypeFilter && questionTypeFilter !== 'all' ? questionTypeFilter : undefined,
                difficulty: difficultyFilter && difficultyFilter !== 'all' ? difficultyFilter : undefined,
                approval_status: approvalFilter && approvalFilter !== 'all' ? approvalFilter : undefined,
                performance_filter: performanceFilter && performanceFilter !== 'all' ? performanceFilter : undefined,
                sort_by: sortBy,
                sort_order: sortOrder,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setSearch('');
        setTopicFilter('all');
        setQuestionTypeFilter('all');
        setDifficultyFilter('all');
        setApprovalFilter('all');
        setPerformanceFilter('all');
        setSortBy('created_at');
        setSortOrder('desc');
        router.get('/analytics/question-bank', {}, { preserveState: true });
    };

    const handleSort = (newSortBy: string) => {
        const newOrder =
            sortBy === newSortBy && sortOrder === 'desc' ? 'asc' : 'desc';
        setSortBy(newSortBy);
        setSortOrder(newOrder);
        router.get(
            '/analytics/question-bank',
            {
                ...filters,
                sort_by: newSortBy,
                sort_order: newOrder,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const hasActiveFilters =
        filters.search ||
        (filters.topic && filters.topic !== 'all') ||
        (filters.question_type && filters.question_type !== 'all') ||
        (filters.difficulty && filters.difficulty !== 'all') ||
        (filters.approval_status && filters.approval_status !== 'all') ||
        (filters.performance_filter && filters.performance_filter !== 'all');

    const getDifficultyBadge = (difficulty: string | null, computed: string | null) => {
        const finalDifficulty = computed || difficulty;
        if (!finalDifficulty) {
            return <Badge variant="outline">Not Set</Badge>;
        }
        const variant =
            finalDifficulty === 'easy'
                ? 'default'
                : finalDifficulty === 'medium'
                  ? 'secondary'
                  : 'destructive';
        return <Badge variant={variant}>{finalDifficulty}</Badge>;
    };

    const formatTime = (seconds: number | null) => {
        if (!seconds) return 'N/A';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}m ${secs}s`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Question Bank Analytics" />

            <AnalyticsLayout>
                <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                    {/* Header */}
                    <div className="flex items-start justify-between gap-4">
                        <HeadingSmall
                            title="Question Bank Analytics"
                            description="Performance metrics and statistics for question bank items"
                        />
                    </div>

                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Questions
                                </CardTitle>
                                <FileQuestion className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {summary.total_questions}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Approved
                                </CardTitle>
                                <CheckCircle2 className="h-4 w-4 text-green-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {summary.approved_questions}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {summary.pending_questions} pending
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Avg Success Rate
                                </CardTitle>
                                <TrendingUp className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {summary.average_success_rate}%
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Questions Analyzed
                                </CardTitle>
                                <BarChart3 className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {
                                        questions.data.filter(
                                            (q) => q.statistics?.times_answered > 0,
                                        ).length
                                    }
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    with performance data
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Filters */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Filters</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <div className="space-y-2">
                                    <Label htmlFor="search">Search</Label>
                                    <div className="relative">
                                        <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="search"
                                            placeholder="Search questions..."
                                            value={search}
                                            onChange={(e) =>
                                                setSearch(e.target.value)
                                            }
                                            className="pl-8"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="topic">Topic</Label>
                                    <Select
                                        value={topicFilter}
                                        onValueChange={setTopicFilter}
                                    >
                                        <SelectTrigger id="topic">
                                            <SelectValue placeholder="All Topics" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Topics</SelectItem>
                                            {topics.map((topic) => (
                                                <SelectItem
                                                    key={topic.id}
                                                    value={topic.id.toString()}
                                                >
                                                    {topic.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="question_type">
                                        Question Type
                                    </Label>
                                    <Select
                                        value={questionTypeFilter}
                                        onValueChange={setQuestionTypeFilter}
                                    >
                                        <SelectTrigger id="question_type">
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
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="difficulty">Difficulty</Label>
                                    <Select
                                        value={difficultyFilter}
                                        onValueChange={setDifficultyFilter}
                                    >
                                        <SelectTrigger id="difficulty">
                                            <SelectValue placeholder="All Difficulties" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Difficulties</SelectItem>
                                            <SelectItem value="easy">Easy</SelectItem>
                                            <SelectItem value="medium">Medium</SelectItem>
                                            <SelectItem value="hard">Hard</SelectItem>
                                            <SelectItem value="computed">
                                                Computed Only
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="approval_status">
                                        Approval Status
                                    </Label>
                                    <Select
                                        value={approvalFilter}
                                        onValueChange={setApprovalFilter}
                                    >
                                        <SelectTrigger id="approval_status">
                                            <SelectValue placeholder="All Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Status</SelectItem>
                                            <SelectItem value="approved">
                                                Approved
                                            </SelectItem>
                                            <SelectItem value="pending">
                                                Pending
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="performance_filter">
                                        Performance
                                    </Label>
                                    <Select
                                        value={performanceFilter}
                                        onValueChange={setPerformanceFilter}
                                    >
                                        <SelectTrigger id="performance_filter">
                                            <SelectValue placeholder="All Performance" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Performance</SelectItem>
                                            <SelectItem value="excellent">
                                                Excellent
                                            </SelectItem>
                                            <SelectItem value="needs_review">
                                                Needs Review
                                            </SelectItem>
                                            <SelectItem value="never_used">
                                                Never Used
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="mt-4 flex items-center gap-2">
                                <Button onClick={handleSearch}>
                                    <Search className="mr-2 h-4 w-4" />
                                    Apply Filters
                                </Button>
                                {hasActiveFilters && (
                                    <Button
                                        variant="outline"
                                        onClick={clearFilters}
                                    >
                                        <X className="mr-2 h-4 w-4" />
                                        Clear Filters
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Questions Table */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Question Performance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Question</TableHead>
                                            <TableHead>Topic</TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead
                                                className="cursor-pointer"
                                                onClick={() =>
                                                    handleSort('difficulty_level')
                                                }
                                            >
                                                Difficulty
                                                {sortBy === 'difficulty_level' && (
                                                    sortOrder === 'asc' ? (
                                                        <TrendingUp className="ml-1 inline h-3 w-3" />
                                                    ) : (
                                                        <TrendingDown className="ml-1 inline h-3 w-3" />
                                                    )
                                                )}
                                            </TableHead>
                                            <TableHead
                                                className="cursor-pointer"
                                                onClick={() =>
                                                    handleSort('success_rate')
                                                }
                                            >
                                                Success Rate
                                                {sortBy === 'success_rate' && (
                                                    sortOrder === 'asc' ? (
                                                        <TrendingUp className="ml-1 inline h-3 w-3" />
                                                    ) : (
                                                        <TrendingDown className="ml-1 inline h-3 w-3" />
                                                    )
                                                )}
                                            </TableHead>
                                            <TableHead
                                                className="cursor-pointer"
                                                onClick={() =>
                                                    handleSort('discrimination_index')
                                                }
                                            >
                                                Discrimination
                                                {sortBy === 'discrimination_index' && (
                                                    sortOrder === 'asc' ? (
                                                        <TrendingUp className="ml-1 inline h-3 w-3" />
                                                    ) : (
                                                        <TrendingDown className="ml-1 inline h-3 w-3" />
                                                    )
                                                )}
                                            </TableHead>
                                            <TableHead
                                                className="cursor-pointer"
                                                onClick={() =>
                                                    handleSort('times_answered')
                                                }
                                            >
                                                Times Answered
                                                {sortBy === 'times_answered' && (
                                                    sortOrder === 'asc' ? (
                                                        <TrendingUp className="ml-1 inline h-3 w-3" />
                                                    ) : (
                                                        <TrendingDown className="ml-1 inline h-3 w-3" />
                                                    )
                                                )}
                                            </TableHead>
                                            <TableHead>Avg Time</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {questions.data.map((question) => (
                                            <TableRow key={question.id}>
                                                <TableCell className="max-w-md">
                                                    <div className="truncate">
                                                        {question.question_text}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {question.topic?.name || (
                                                        <span className="text-muted-foreground">
                                                            No Topic
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">
                                                        {question.question_type
                                                            .replace('_', ' ')
                                                            .replace(
                                                                /\b\w/g,
                                                                (l) =>
                                                                    l.toUpperCase(),
                                                            )}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {getDifficultyBadge(
                                                        question.difficulty_level,
                                                        question.statistics
                                                            ?.computed_difficulty ||
                                                            null,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {question.statistics &&
                                                    question.statistics
                                                        .times_answered > 0 ? (
                                                        <div className="flex items-center gap-2">
                                                            <span
                                                                className={
                                                                    (question
                                                                        .statistics
                                                                        ?.success_rate ||
                                                                        0) >= 70
                                                                        ? 'text-green-600'
                                                                        : (question
                                                                              .statistics
                                                                              ?.success_rate ||
                                                                            0) >=
                                                                            40
                                                                          ? 'text-yellow-600'
                                                                          : 'text-red-600'
                                                                }
                                                            >
                                                                {
                                                                    question
                                                                        .statistics
                                                                        ?.success_rate
                                                                }
                                                                %
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            No data
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {question.statistics &&
                                                    question.statistics
                                                        .times_answered > 0 &&
                                                    question.statistics
                                                        .discrimination_index !==
                                                        null ? (
                                                        <Badge
                                                            variant={
                                                                (question
                                                                    .statistics
                                                                    ?.discrimination_index ||
                                                                    0) >= 0.3
                                                                    ? 'default'
                                                                    : (question
                                                                          .statistics
                                                                          ?.discrimination_index ||
                                                                        0) >=
                                                                        0.2
                                                                      ? 'secondary'
                                                                      : 'destructive'
                                                            }
                                                        >
                                                            {
                                                                question
                                                                    .statistics
                                                                    ?.discrimination_index
                                                            }
                                                        </Badge>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            No data
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {question.statistics
                                                        ?.times_answered || 0}
                                                    {question.statistics
                                                        ?.times_answered ===
                                                        0 && (
                                                        <span className="ml-1 text-xs text-muted-foreground">
                                                            (unused)
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatTime(
                                                        question.statistics
                                                            ?.average_time_seconds ||
                                                            null,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {question.is_approved ? (
                                                        <Badge variant="default">
                                                            <CheckCircle2 className="mr-1 h-3 w-3" />
                                                            Approved
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="secondary">
                                                            <AlertTriangle className="mr-1 h-3 w-3" />
                                                            Pending
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Pagination */}
                            {questions.data.length > 0 && (
                                <div className="mt-4 space-y-4">
                                    <div className="text-center text-sm text-muted-foreground">
                                        Showing{' '}
                                        {(questions.current_page - 1) *
                                            questions.per_page +
                                            1}{' '}
                                        to{' '}
                                        {Math.min(
                                            questions.current_page *
                                                questions.per_page,
                                            questions.total,
                                        )}{' '}
                                        of {questions.total} questions
                                    </div>
                                    {questions.last_page > 1 && (
                                        <div className="flex items-center justify-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={
                                                    questions.current_page === 1
                                                }
                                                onClick={() => {
                                                    router.get(
                                                        '/analytics/question-bank',
                                                        {
                                                            ...filters,
                                                            page:
                                                                questions.current_page -
                                                                1,
                                                        },
                                                        {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }}
                                            >
                                                Previous
                                            </Button>
                                            {Array.from(
                                                { length: questions.last_page },
                                                (_, i) => i + 1,
                                            )
                                                .filter((page) => {
                                                    if (page === 1) return true;
                                                    if (
                                                        page ===
                                                        questions.last_page
                                                    )
                                                        return true;
                                                    if (
                                                        page >=
                                                            questions.current_page -
                                                                1 &&
                                                        page <=
                                                            questions.current_page +
                                                                1
                                                    )
                                                        return true;
                                                    return false;
                                                })
                                                .map((page, index, array) => {
                                                    const showEllipsisBefore =
                                                        index > 0 &&
                                                        array[index - 1] <
                                                            page - 1;
                                                    return (
                                                        <div
                                                            key={page}
                                                            className="flex items-center gap-2"
                                                        >
                                                            {showEllipsisBefore && (
                                                                <span className="px-2 text-muted-foreground">
                                                                    ...
                                                                </span>
                                                            )}
                                                            <Button
                                                                variant={
                                                                    page ===
                                                                    questions.current_page
                                                                        ? 'default'
                                                                        : 'outline'
                                                                }
                                                                size="sm"
                                                                onClick={() => {
                                                                    router.get(
                                                                        '/analytics/question-bank',
                                                                        {
                                                                            ...filters,
                                                                            page,
                                                                        },
                                                                        {
                                                                            preserveState:
                                                                                true,
                                                                            preserveScroll:
                                                                                true,
                                                                        },
                                                                    );
                                                                }}
                                                            >
                                                                {page}
                                                            </Button>
                                                        </div>
                                                    );
                                                })}
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={
                                                    questions.current_page ===
                                                    questions.last_page
                                                }
                                                onClick={() => {
                                                    router.get(
                                                        '/analytics/question-bank',
                                                        {
                                                            ...filters,
                                                            page:
                                                                questions.current_page +
                                                                1,
                                                        },
                                                        {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }}
                                            >
                                                Next
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            )}

                            {questions.data.length === 0 && (
                                <div className="py-8 text-center">
                                    <p className="text-muted-foreground">
                                        No questions found matching your filters.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </AnalyticsLayout>
        </AppLayout>
    );
}
