import { CreateQuestionSheet } from '@/components/question-bank/create-question-sheet';
import { EditQuestionSheet } from '@/components/question-bank/edit-question-sheet';
import { QuestionLogsSheet } from '@/components/question-bank/question-logs-sheet';
import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import { QuestionsImportPreviewDialog } from '@/components/questions-import-preview-dialog';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { BarChart3, Check, CheckCircle, Clock, Download, Edit, FileText, Plus, Search, Trash2, TrendingUp, Upload } from 'lucide-react';
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
    const [previewData, setPreviewData] = useState<any>(null);
    const [isLoadingPreview, setIsLoadingPreview] = useState(false);
    const [isImporting, setIsImporting] = useState(false);

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

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
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
                            <Button type="submit">Search</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Questions List */}
                {questions.data.length === 0 ? (
                    <Card>
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
                                    <Card key={question.id}>
                                        <CardContent className="pt-6">
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="flex-1 space-y-3">
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
                                                        <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                            <div className="flex items-center gap-1">
                                                                <TrendingUp className="h-4 w-4" />
                                                                <span>Success: {typeof stats.success_rate === 'number' ? stats.success_rate.toFixed(0) : Number(stats.success_rate || 0).toFixed(0)}%</span>
                                                            </div>
                                                            <div className="flex items-center gap-1">
                                                                <Check className="h-4 w-4" />
                                                                <span>{stats.times_answered} attempts</span>
                                                            </div>
                                                            {stats.average_time_seconds && (
                                                                <div className="flex items-center gap-1">
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
                                                        onClick={() => handleEdit(question)}
                                                    >
                                                        <Edit className="mr-1 h-3 w-3" />
                                                        Edit
                                                    </Button>
                                                    <Button 
                                                        size="sm" 
                                                        variant="outline"
                                                        onClick={() => setViewingLogsQuestion(question)}
                                                    >
                                                        <FileText className="mr-1 h-3 w-3" />
                                                        Logs
                                                    </Button>
                                                    {!question.is_approved && (
                                                        <Button 
                                                            size="sm"
                                                            onClick={() => handleApprove(question.id)}
                                                        >
                                                            <CheckCircle className="mr-1 h-3 w-3" />
                                                            Approve
                                                        </Button>
                                                    )}
                                                    <Button 
                                                        size="sm" 
                                                        variant="destructive"
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

