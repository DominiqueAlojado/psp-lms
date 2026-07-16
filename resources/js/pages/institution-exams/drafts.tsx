import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/hooks/use-permissions';
import { formatRelativeTime } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import InstitutionExamsLayout from '@/layouts/exams/institution-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ClipboardList, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Institution Exams',
        href: '/institution-exams/active',
    },
];

interface Exam {
    id: number;
    title: string;
    description: string | null;
    exam_category: string | null;
    questions_count: number;
    total_points: number;
    passing_score: number;
    duration_minutes: number | null;
    is_published: boolean;
    is_available: boolean;
    created_by: string;
    updated_at: string;
}

interface PaginatedExams {
    data: Exam[];
    total: number;
}

interface PageProps {
    exams: PaginatedExams;
    filters: {
        search?: string;
    };
    [key: string]: unknown;
}

export default function Drafts() {
    const { hasPermission } = usePermissions();
    const { exams } = usePage<PageProps>().props;
    const [deletingExam, setDeletingExam] = useState<Exam | null>(null);

    const confirmDeleteExam = () => {
        if (!deletingExam) {
            return;
        }

        router.delete(`/assessments/${deletingExam.id}`, {
            preserveScroll: true,
            onFinish: () => setDeletingExam(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Institution Exams – Drafts" />

            <InstitutionExamsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <HeadingSmall
                            title="Draft Institution Exams"
                            description="Unpublished exams that are still in draft"
                        />
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <span className="inline-block">
                                        <Button
                                            asChild
                                            disabled={
                                                !hasPermission(
                                                    'create-assessments',
                                                )
                                            }
                                        >
                                            <Link href="/institution-exams/create">
                                                <Plus className="mr-2 h-4 w-4" />
                                                Create Exam
                                            </Link>
                                        </Button>
                                    </span>
                                </TooltipTrigger>
                                {!hasPermission('create-assessments') && (
                                    <TooltipContent>
                                        <p>
                                            You don't have permission to create
                                            assessments
                                        </p>
                                    </TooltipContent>
                                )}
                            </Tooltip>
                        </TooltipProvider>
                    </div>

                    {exams.data.length === 0 ? (
                        <div className="rounded-lg border p-6 text-center">
                            <ClipboardList className="mx-auto h-10 w-10 text-muted-foreground" />
                            <p className="mt-2 text-sm text-muted-foreground">
                                No draft exams. Create a new exam or publish
                                existing ones!
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {exams.data.map((exam) => (
                                <div
                                    key={exam.id}
                                    className="rounded-lg border p-4 hover:bg-muted/50"
                                >
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2">
                                                <h3 className="font-semibold">
                                                    {exam.title}
                                                </h3>
                                                {exam.exam_category && (
                                                    <Badge
                                                        variant="outline"
                                                        className="text-xs"
                                                    >
                                                        {exam.exam_category}
                                                    </Badge>
                                                )}
                                            </div>
                                            {exam.description && (
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {exam.description}
                                                </p>
                                            )}
                                            <div className="mt-2 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                <span>
                                                    {exam.questions_count}{' '}
                                                    questions
                                                </span>
                                                <span>
                                                    {exam.total_points} points
                                                </span>
                                                {exam.duration_minutes && (
                                                    <span>
                                                        {exam.duration_minutes}{' '}
                                                        min
                                                    </span>
                                                )}
                                                <span>
                                                    MPL: {exam.passing_score}
                                                </span>
                                                <span>
                                                    By {exam.created_by}
                                                </span>
                                                <span>
                                                    Updated {formatRelativeTime(exam.updated_at)}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="secondary">
                                                Draft
                                            </Badge>
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <span
                                                            className={
                                                                !hasPermission(
                                                                    'edit-assessments',
                                                                )
                                                                    ? 'inline-block'
                                                                    : ''
                                                            }
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                asChild={hasPermission(
                                                                    'edit-assessments',
                                                                )}
                                                                disabled={
                                                                    !hasPermission(
                                                                        'edit-assessments',
                                                                    )
                                                                }
                                                            >
                                                                {hasPermission(
                                                                    'edit-assessments',
                                                                ) ? (
                                                                    <Link
                                                                        href={`/institution-exams/${exam.id}/edit`}
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </Link>
                                                                ) : (
                                                                    <span>
                                                                        <Pencil className="h-4 w-4" />
                                                                    </span>
                                                                )}
                                                            </Button>
                                                        </span>
                                                    </TooltipTrigger>
                                                    {!hasPermission(
                                                        'edit-assessments',
                                                    ) && (
                                                        <TooltipContent>
                                                            <p>
                                                                You don't have
                                                                permission to
                                                                edit assessments
                                                            </p>
                                                        </TooltipContent>
                                                    )}
                                                </Tooltip>
                                            </TooltipProvider>
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <span
                                                            className={
                                                                !hasPermission(
                                                                    'delete-assessments',
                                                                )
                                                                    ? 'inline-block'
                                                                    : ''
                                                            }
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                disabled={
                                                                    !hasPermission(
                                                                        'delete-assessments',
                                                                    )
                                                                }
                                                                onClick={() =>
                                                                    setDeletingExam(
                                                                        exam,
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 className="h-4 w-4 text-destructive" />
                                                            </Button>
                                                        </span>
                                                    </TooltipTrigger>
                                                    {!hasPermission(
                                                        'delete-assessments',
                                                    ) && (
                                                        <TooltipContent>
                                                            <p>
                                                                You don't have
                                                                permission to
                                                                delete
                                                                assessments
                                                            </p>
                                                        </TooltipContent>
                                                    )}
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </InstitutionExamsLayout>

            <DeleteConfirmationDialog
                open={!!deletingExam}
                title="Delete Draft Exam?"
                itemName={deletingExam?.title}
                description={deletingExam?.description ?? undefined}
                warningMessage="This action cannot be undone. This will permanently delete this draft exam and its associated data."
                confirmText="Delete Draft Exam"
                onConfirm={confirmDeleteExam}
                onCancel={() => setDeletingExam(null)}
            />
        </AppLayout>
    );
}
