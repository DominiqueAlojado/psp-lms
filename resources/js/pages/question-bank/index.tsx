import { CreateQuestionSheet } from '@/components/question-bank/create-question-sheet';
import { EditQuestionSheet } from '@/components/question-bank/edit-question-sheet';
import { QuestionLogsSheet } from '@/components/question-bank/question-logs-sheet';
import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import { QuestionsImportPreviewDialog } from '@/components/questions-import-preview-dialog';
import HeadingSmall from '@/components/heading-small';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import {
    BarChart3,
    Check,
    CheckCircle,
    Clock,
    Download,
    Edit,
    FileText,
    Plus,
    Search,
    Trash2,
    TrendingUp,
    Upload,
} from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Question Bank', href: '/question-bank' },
];

interface Question {
    id: number;
    question_text: string;
    question_type: string;
    points: number;
    difficulty_level: string | null;
    is_approved: boolean;
    times_used: number;
    topic_id: number | null;
    explanation: string;
    image_path: string | null;
    topic: { id: number; name: string } | null;
    choices: Array<{
        choice_text: string;
        is_correct: boolean;
    }>;
    statistics: {
        times_answered: number;
        success_rate: number;
        average_time_seconds: number | null;
        computed_difficulty: string | null;
    } | null;
}

interface PaginatedQuestions {
    data: Question[];
    total: number;
    current_page: number;
    last_page: number;
}

interface PageProps {
    questions: PaginatedQuestions;
    filters: {
        search?: string;
        topic?: string;
        type?: string;
        difficulty?: string;
        approval?: string;
    };
}

interface ImportPreviewData {
    questions?: unknown[];
    errors?: unknown[];
    total_valid?: number;
    total_errors?: number;
}

const typeLabels: Record<string, string> = {
    multiple_choice: 'Multiple Choice',
    multiple_select: 'Multiple Select',
    true_false: 'True/False',
};

const difficultyColors: Record<string, string> = {
    easy: 'border border-emerald-200/70 bg-emerald-50 text-emerald-700',
    medium: 'border border-amber-200/80 bg-amber-50 text-amber-700',
    hard: 'border border-rose-200/80 bg-rose-50 text-rose-700',
};

export default function QuestionBankIndex({ questions, filters }: PageProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [showCreateSheet, setShowCreateSheet] = useState(false);
    const [selectedQuestion, setSelectedQuestion] = useState<Question | null>(null);
    const [showEditSheet, setShowEditSheet] = useState(false);
    const [questionToDelete, setQuestionToDelete] = useState<Question | null>(null);
    const [viewingLogsQuestion, setViewingLogsQuestion] = useState<Question | null>(null);
    
    // Import state
    const [showImportPreview, setShowImportPreview] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);
    const [previewData, setPreviewData] = useState<ImportPreviewData | null>(null);
    const [isLoadingPreview, setIsLoadingPreview] = useState(false);
    const [isImporting, setIsImporting] = useState(false);
    const approvedCount = questions.data.filter((question) => question.is_approved).length;
    const withStatsCount = questions.data.filter(
        (question) => (question.statistics?.times_answered ?? 0) > 0,
    ).length;
    const totalUsageCount = questions.data.reduce(
        (total, question) => total + question.times_used,
        0,
    );

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/question-bank',
            { ...filters, search: searchQuery },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get(
            '/question-bank',
            { ...filters, [key]: value },
            { preserveState: true, preserveScroll: true }
        );
    };

    const getDifficulty = (question: Question) => {
        return question.statistics?.computed_difficulty || question.difficulty_level || 'medium';
    };

    const handleApprove = (questionId: number) => {
        router.post(`/question-bank/${questionId}/approve`, {}, {
            preserveScroll: true,
        });
    };

    const handleEdit = (question: Question) => {
        setSelectedQuestion(question);
        setShowEditSheet(true);
    };

    const handleDelete = (question: Question) => {
        setQuestionToDelete(question);
    };

    const confirmDelete = () => {
        if (!questionToDelete) return;
        router.delete(`/question-bank/${questionToDelete.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setQuestionToDelete(null);
            },
        });
    };

    const handleImportFile = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setImportFile(file);
        setIsLoadingPreview(true);

        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch('/question-bank/preview-import', {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(errorText || 'Failed to preview import.');
            }

            const data = await response.json();
            setPreviewData(data);
            setShowImportPreview(true);
        } catch (error) {
            console.error('Preview failed:', error);
        } finally {
            setIsLoadingPreview(false);
            e.target.value = '';
        }
    };

    const handleConfirmImport = () => {
        if (!importFile) return;

        setIsImporting(true);

        const formData = new FormData();
        formData.append('file', importFile);

        router.post('/question-bank/import', formData, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setShowImportPreview(false);
                setPreviewData(null);
                setImportFile(null);
            },
            onError: (errors) => {
                console.error('Import failed:', errors);
            },
            onFinish: () => {
                setIsImporting(false);
            },
        });
    };

    const handleCancelImport = () => {
        setShowImportPreview(false);
        setPreviewData(null);
        setImportFile(null);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Question Bank" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-start justify-between gap-4">
                    <HeadingSmall
                        title="Question Bank"
                        description="Centralized repository of reusable questions"
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" onClick={() => router.visit('/question-bank/statistics')}>
                            <BarChart3 className="mr-2 h-4 w-4" />
                            Statistics
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => window.open('/assessments/questions/template', '_blank')}
                        >
                            <Download className="mr-2 h-4 w-4" />
                            Empty Template
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => document.getElementById('question-bank-import')?.click()}
                            disabled={isLoadingPreview}
                        >
                            <Upload className="mr-2 h-4 w-4" />
                            {isLoadingPreview ? 'Loading...' : 'Import from Excel'}
                        </Button>
                        <Button onClick={() => setShowCreateSheet(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Create Question
                        </Button>
                    </div>
                    <input
                        id="question-bank-import"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        className="hidden"
                        onChange={handleImportFile}
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Visible Questions"
                        value={questions.total}
                        description="Questions available in the current bank"
                        icon={FileText}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Approved"
                        value={approvedCount}
                        description="Approved questions on this page"
                        icon={CheckCircle}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="With Performance Data"
                        value={withStatsCount}
                        description="Questions with answer history"
                        icon={TrendingUp}
                        iconColor="text-primary"
                    />
                    <StatCard
                        title="Used In Exams"
                        value={totalUsageCount}
                        description="Total times these questions were used"
                        icon={BarChart3}
                        iconColor="text-primary"
                    />
                </div>

                <Card className="overflow-hidden border-primary/10 bg-[linear-gradient(135deg,rgba(248,244,255,0.98),rgba(255,255,255,0.94))]">
                    <CardContent className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="space-y-1">
                            <p className="text-[0.7rem] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Question system
                            </p>
                            <h3 className="text-2xl font-semibold tracking-[-0.04em] text-foreground">
                                Reusable questions with cleaner hierarchy
                            </h3>
                            <p className="text-sm leading-6 text-muted-foreground">
                                The page now leans on softer violet surfaces and toned-down success and difficulty colors so the content feels more balanced.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">
                                {approvedCount} approved on this page
                            </Badge>
                            <Badge variant="outline">
                                {questions.current_page} / {questions.last_page} pages
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                    <CardContent className="space-y-5 pt-6">
                        <form onSubmit={handleSearch} className="flex flex-col gap-4 md:flex-row">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Search questions..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                            <Select value={filters.type} onValueChange={(value) => handleFilterChange('type', value)}>
                                <SelectTrigger className="w-full md:w-[180px]">
                                    <SelectValue placeholder="All Types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="multiple_choice">Multiple Choice</SelectItem>
                                    <SelectItem value="multiple_select">Multiple Select</SelectItem>
                                    <SelectItem value="true_false">True/False</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={filters.approval} onValueChange={(value) => handleFilterChange('approval', value)}>
                                <SelectTrigger className="w-full md:w-[180px]">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="approved">Approved</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="submit" className="md:min-w-28">
                                Search
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Questions List */}
                {questions.data.length === 0 ? (
                    <Card className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.94))]">
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <p className="text-lg font-medium">No questions found</p>
                            <p className="mb-4 text-muted-foreground">
                                Create your first question to build your question bank
                            </p>
                            <Button onClick={() => setShowCreateSheet(true)}>
                                <Plus className="mr-2 h-4 w-4" />
                                Create Question
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <div className="space-y-4">
                            {questions.data.map((question) => {
                                const difficulty = getDifficulty(question);
                                const stats = question.statistics;

                                return (
                                    <Card
                                        key={question.id}
                                        className="overflow-hidden border-border/75 bg-[linear-gradient(180deg,rgba(255,255,255,0.99),rgba(255,255,255,0.95))]"
                                    >
                                        <CardContent className="pt-6">
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="flex-1 space-y-3">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        {question.is_approved && (
                                                            <Badge className="border border-emerald-200/80 bg-emerald-500 text-white shadow-[0_16px_32px_-24px_rgba(16,185,129,0.8)]">
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
                                                            <Badge variant="secondary" className="bg-primary/[0.08] text-primary">
                                                                {question.topic.name}
                                                            </Badge>
                                                        )}
                                                        <Badge variant="outline">{question.points} pts</Badge>
                                                    </div>

                                                    <div
                                                        className="line-clamp-2 text-[0.97rem] font-medium text-foreground"
                                                        dangerouslySetInnerHTML={{ __html: question.question_text }}
                                                    />

                                                    {stats && stats.times_answered > 0 && (
                                                        <div className="flex flex-wrap gap-3 text-sm text-muted-foreground">
                                                            <div className="flex items-center gap-1 rounded-full border border-border/70 bg-background/80 px-3 py-1.5">
                                                                <TrendingUp className="h-4 w-4" />
                                                                <span>Success: {typeof stats.success_rate === 'number' ? stats.success_rate.toFixed(0) : Number(stats.success_rate || 0).toFixed(0)}%</span>
                                                            </div>
                                                            <div className="flex items-center gap-1 rounded-full border border-border/70 bg-background/80 px-3 py-1.5">
                                                                <Check className="h-4 w-4" />
                                                                <span>{stats.times_answered} attempts</span>
                                                            </div>
                                                            {stats.average_time_seconds && (
                                                                <div className="flex items-center gap-1 rounded-full border border-border/70 bg-background/80 px-3 py-1.5">
                                                                    <Clock className="h-4 w-4" />
                                                                    <span>Avg: {Math.round(stats.average_time_seconds)}s</span>
                                                                </div>
                                                            )}
                                                        </div>
                                                    )}

                                                    <div className="text-sm text-muted-foreground">
                                                        Used in {question.times_used} exams
                                                    </div>
                                                </div>
                                                <div className="flex flex-col gap-2">
                                                    <Button 
                                                        size="sm" 
                                                        variant="outline"
                                                        className="justify-start bg-background/90"
                                                        onClick={() => handleEdit(question)}
                                                    >
                                                        <Edit className="mr-1 h-3 w-3" />
                                                        Edit
                                                    </Button>
                                                    <Button 
                                                        size="sm" 
                                                        variant="outline"
                                                        className="justify-start bg-background/90"
                                                        onClick={() => setViewingLogsQuestion(question)}
                                                    >
                                                        <FileText className="mr-1 h-3 w-3" />
                                                        Logs
                                                    </Button>
                                                    {!question.is_approved && (
                                                        <Button 
                                                            size="sm"
                                                            className="justify-start"
                                                            onClick={() => handleApprove(question.id)}
                                                        >
                                                            <CheckCircle className="mr-1 h-3 w-3" />
                                                            Approve
                                                        </Button>
                                                    )}
                                                    <Button 
                                                        size="sm" 
                                                        variant="destructive"
                                                        className="justify-start shadow-[0_18px_34px_-24px_rgba(239,68,68,0.7)]"
                                                        onClick={() => handleDelete(question)}
                                                    >
                                                        <Trash2 className="mr-1 h-3 w-3" />
                                                        Delete
                                                    </Button>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>

                        {/* Pagination */}
                        {questions.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
                                {Array.from({ length: questions.last_page }, (_, i) => i + 1).map((page) => (
                                    <Button
                                        key={page}
                                        variant={page === questions.current_page ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                '/question-bank',
                                                { ...filters, page },
                                                { preserveState: true, preserveScroll: true }
                                            )
                                        }
                                    >
                                        {page}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>

            <CreateQuestionSheet 
                open={showCreateSheet} 
                onOpenChange={setShowCreateSheet} 
            />

            <EditQuestionSheet
                question={selectedQuestion}
                open={showEditSheet}
                onOpenChange={setShowEditSheet}
            />

            <DeleteConfirmationDialog
                open={!!questionToDelete}
                onOpenChange={(open) => !open && setQuestionToDelete(null)}
                onConfirm={confirmDelete}
                title="Delete Question"
                description={`Are you sure you want to delete this question? ${questionToDelete?.times_used ? `This question is used in ${questionToDelete.times_used} exam(s).` : ''}`}
            />

            {previewData && (
                <QuestionsImportPreviewDialog
                    open={showImportPreview}
                    questions={previewData.questions || []}
                    errors={previewData.errors || []}
                    totalValid={previewData.total_valid || 0}
                    totalErrors={previewData.total_errors || 0}
                    onConfirm={handleConfirmImport}
                    onCancel={handleCancelImport}
                    isImporting={isImporting}
                />
            )}

            {/* Question Logs Sheet */}
            <QuestionLogsSheet
                question={viewingLogsQuestion}
                open={!!viewingLogsQuestion}
                onOpenChange={(open) => {
                    if (!open) {
                        setViewingLogsQuestion(null);
                    }
                }}
            />
        </AppLayout>
    );
}
